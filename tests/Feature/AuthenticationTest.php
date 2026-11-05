<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Fortify;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200)
            ->assertSee('name="session-heartbeat-url" content="/session/heartbeat"', false)
            ->assertSee('data-session-expired-notice', false);
    }

    public function test_guest_session_heartbeat_returns_no_content(): void
    {
        $this->post(route('session.heartbeat'))->assertNoContent();
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_legacy_parent_customer_accounts_cannot_sign_in(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('parent', 'web'));

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_role_login_goes_directly_to_the_workspace_instead_of_profile(): void
    {
        \Spatie\Permission\Models\Role::findOrCreate('super-admin');
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $this->get(route('profile.show'))->assertRedirect(route('login'));

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_successful_login_records_a_database_session_and_login_time(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create(['last_login_at' => null]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertNotNull($user->fresh()->last_login_at);
        $session = DB::table('sessions')->where('user_id', $user->id)->first();
        $this->assertNotNull($session);
        $this->assertGreaterThanOrEqual(now()->subMinute()->timestamp, $session->last_activity);
    }

    public function test_login_time_is_recorded_only_after_two_factor_challenge_succeeds(): void
    {
        $user = User::factory()->create(['last_login_at' => null]);
        $user->forceFill([
            'two_factor_secret' => Fortify::currentEncrypter()->encrypt('test-secret'),
            'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode(['test-recovery-code'])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.login'));

        $this->assertGuest();
        $this->assertNull($user->fresh()->last_login_at);
        $challenge = $this->get(route('two-factor.login'))->assertOk();
        $this->assertStringContainsString('no-store', $challenge->headers->get('Cache-Control'));

        $this->post(route('two-factor.login.store'), [
            'recovery_code' => 'test-recovery-code',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $this->assertNull($user->fresh()->last_login_at);
    }

    public function test_logout_clears_all_database_browser_sessions_and_api_tokens(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create();
        $user->createToken('other-device');
        foreach (['browser-a', 'browser-b'] as $id) {
            DB::table('sessions')->insert([
                'id' => $id, 'user_id' => $user->id, 'ip_address' => '127.0.0.1',
                'user_agent' => 'Test browser', 'payload' => '', 'last_activity' => time(),
            ]);
        }

        $this->actingAs($user)->post('/logout')->assertRedirect();

        $this->assertDatabaseMissing('sessions', ['id' => 'browser-a']);
        $this->assertDatabaseMissing('sessions', ['id' => 'browser-b']);
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertGuest();
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_logout_invalidates_a_remember_me_cookie(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'remember' => 'on',
        ])->assertRedirect(route('dashboard', absolute: false));

        $token = $user->fresh()->getRememberToken();
        $this->assertNotEmpty($token);

        $this->post(route('logout'))->assertRedirect();

        $this->assertGuest();
        $this->assertNotSame($token, $user->fresh()->getRememberToken());
    }

    public function test_logout_blocks_all_private_role_pages_and_prevents_cached_responses(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create();
        \Spatie\Permission\Models\Role::findOrCreate('super-admin');
        $user->assignRole('super-admin');

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard', absolute: false));
        $this->get(route('admin.dashboard'))->assertOk();

        $logoutResponse = $this->post(route('logout'))->assertRedirect();
        $this->assertStringContainsString('no-store', $logoutResponse->headers->get('Cache-Control'));
        $this->assertGuest();
        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->app['auth']->forgetGuards();

        foreach (['dashboard', 'admin.dashboard', 'finance.dashboard', 'supplier.dashboard', 'driver.dashboard', 'school.dashboard', 'password.change'] as $route) {
            $response = $this->get(route($route))
                ->assertRedirect(route('login'))
                ->assertHeader('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT')
                ->assertHeader('X-Content-Type-Options', 'nosniff');
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        }
    }

    public function test_idle_database_session_is_rejected_after_its_configured_lifetime(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));
        $this->get(route('dashboard'))->assertOk();

        $sessionId = DB::table('sessions')->where('user_id', $user->id)->value('id');
        $this->assertNotNull($sessionId);
        $updated = DB::table('sessions')
            ->where('id', $sessionId)
            ->update(['last_activity' => now()->subMinutes(config('session.lifetime') + 1)->timestamp]);
        $this->assertSame(1, $updated);

        $this->assertSame('', $this->app['session']->driver('database')->getHandler()->read($sessionId));
    }

    public function test_inactive_account_with_a_stale_web_session_is_signed_out(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $response = $this->actingAs($user)->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->app['auth']->forgetGuards();
        $this->assertGuest();
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_authenticated_pages_are_not_cached_by_the_browser(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertSee('data-private-page', false);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_login_page_cannot_be_restored_from_http_cache(): void
    {
        $response = $this->get(route('login'))->assertOk()
            ->assertSee('data-private-page', false);

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_browser_security_headers_are_applied(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), geolocation=(), microphone=()')
            ->assertHeader('Content-Security-Policy', "base-uri 'self'; frame-ancestors 'self'; object-src 'none'");
    }
}
