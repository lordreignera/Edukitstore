<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
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

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
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
}
