<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndSuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeededAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_accounts_have_temporary_credentials_and_workspace_access(): void
    {
        config(['edukit.seed_demo_accounts' => true]);
        $this->seed(RolesAndSuperAdminSeeder::class);

        foreach ($this->accounts() as $account) {
            $this->post('/logout');
            $this->flushSession();
            $this->app['auth']->forgetGuards();

            $user = User::where('email', $account['email'])->firstOrFail();

            $this->assertTrue(Hash::check($account['password'], $user->password));
            $this->assertTrue($user->is_active);
            $this->assertTrue($user->must_change_password);
            $this->assertTrue($user->hasRole($account['role']));

            $landingRoute = match ($account['role']) {
                'super-admin', 'admin' => 'admin.dashboard',
                'finance-admin' => 'finance.dashboard',
                'supplier' => 'supplier.dashboard',
                'delivery-person' => 'driver.dashboard',
                'school-admin', 'school-receiver' => 'school.dashboard',
                'parent' => 'parent.dashboard',
            };

            $this->post('/login', [
                'email' => $account['email'],
                'password' => $account['password'],
            ])->assertRedirect(route($landingRoute, absolute: false));

            $this->get(route('dashboard'))->assertRedirect(route($landingRoute));
            $this->get(route($landingRoute))
                ->assertOk()
                ->assertSee('Security reminder:')
                ->assertSee(route('password.change'));

            $user->update(['must_change_password' => false]);
            $this->post('/logout');
            $this->flushSession();
            $this->app['auth']->forgetGuards();

            $this->post('/login', [
                'email' => $account['email'],
                'password' => $account['password'],
            ])->assertRedirect(route($landingRoute, absolute: false));
            $response = $this->get(route('dashboard'));

            $response->assertRedirect(route($landingRoute));
            $this->get(route($landingRoute))->assertOk()->assertDontSee('Security reminder:');
        }
    }

    public function test_finance_school_and_parent_workspaces_are_role_scoped(): void
    {
        config(['edukit.seed_demo_accounts' => true]);
        $this->seed(RolesAndSuperAdminSeeder::class);

        $finance = User::where('email', 'finance@edukit.test')->firstOrFail();
        $this->actingAs($finance)->get(route('finance.dashboard'))->assertOk()->assertSee('Security reminder:');
        $finance->update(['must_change_password' => false]);
        $this->actingAs($finance)->get(route('finance.dashboard'))->assertOk()->assertSee('Finance Dashboard');
        $this->get(route('password.change'))->assertRedirect(route('dashboard'));
        $this->get(route('admin.users.index'))->assertForbidden();
        $this->get(route('school.dashboard'))->assertForbidden();

        $this->flushSession();
        $this->app['auth']->forgetGuards();
        $schoolAdmin = User::where('email', 'schooladmin@edukit.test')->firstOrFail();
        $schoolAdmin->update(['must_change_password' => false]);
        $this->actingAs($schoolAdmin)->get(route('school.dashboard'))->assertOk()->assertSee('School Administrator Dashboard');
        $this->get(route('finance.dashboard'))->assertForbidden();

        $this->flushSession();
        $this->app['auth']->forgetGuards();
        $receiver = User::where('email', 'receiver@edukit.test')->firstOrFail();
        $receiver->update(['must_change_password' => false]);
        $this->actingAs($receiver)->get(route('school.dashboard'))->assertOk()->assertSee('School Receiver Dashboard');
        $this->get(route('admin.dashboard'))->assertForbidden();

        $this->flushSession();
        $this->app['auth']->forgetGuards();
        $parent = User::where('email', 'parent@edukit.test')->firstOrFail();
        $parent->update(['must_change_password' => false]);
        $this->actingAs($parent)->get(route('parent.dashboard'))->assertOk()->assertSee('Parent Dashboard');
        $this->get(route('school.dashboard'))->assertForbidden();
    }

    public function test_every_seeded_role_is_restricted_to_its_own_private_workspace(): void
    {
        config(['edukit.seed_demo_accounts' => true]);
        $this->seed(RolesAndSuperAdminSeeder::class);

        $workspaces = [
            'admin.dashboard' => ['super-admin', 'admin'],
            'finance.dashboard' => ['finance-admin'],
            'supplier.dashboard' => ['supplier'],
            'driver.dashboard' => ['delivery-person'],
            'school.dashboard' => ['school-admin', 'school-receiver'],
            'parent.dashboard' => ['parent'],
        ];

        foreach ($this->accounts() as $account) {
            $this->flushSession();
            $this->app['auth']->forgetGuards();
            $user = User::where('email', $account['email'])->firstOrFail();

            foreach ($workspaces as $route => $allowedRoles) {
                $response = $this->actingAs($user)->get(route($route));

                if (in_array($account['role'], $allowedRoles, true)) {
                    $response->assertOk()->assertSee('data-private-page', false);
                } else {
                    $response->assertForbidden()->assertHeader('X-Content-Type-Options', 'nosniff');
                }

                $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
            }
        }
    }

    public function test_each_seeded_workspace_can_open_and_download_its_user_guide(): void
    {
        config(['edukit.seed_demo_accounts' => true]);
        $this->seed(RolesAndSuperAdminSeeder::class);

        foreach ($this->accounts() as $account) {
            $this->flushSession();
            $this->app['auth']->forgetGuards();

            $user = User::where('email', $account['email'])->firstOrFail();

            $this->actingAs($user)
                ->get(route('user-guide'))
                ->assertOk()
                ->assertSee('User guide')
                ->assertSee('Download guide');

            $this->get(route('user-guide.download'))
                ->assertOk()
                ->assertHeader('Content-Disposition');
        }
    }

    /**
     * @return array<int, array{email: string, password: string, role: string}>
     */
    private function accounts(): array
    {
        return [
            ['email' => 'superadmin@edukit.test', 'password' => 'Edukit@Super2026!Secure', 'role' => 'super-admin'],
            ['email' => 'admin@edukit.test', 'password' => 'Edukit@Admin2026!Secure', 'role' => 'admin'],
            ['email' => 'finance@edukit.test', 'password' => 'Edukit@Finance2026!Secure', 'role' => 'finance-admin'],
            ['email' => 'supplier@edukit.test', 'password' => 'Edukit@Supplier2026!Secure', 'role' => 'supplier'],
            ['email' => 'driver@edukit.test', 'password' => 'Edukit@Driver2026!Secure', 'role' => 'delivery-person'],
            ['email' => 'schooladmin@edukit.test', 'password' => 'Edukit@School2026!Secure', 'role' => 'school-admin'],
            ['email' => 'receiver@edukit.test', 'password' => 'Edukit@Receiver2026!Secure', 'role' => 'school-receiver'],
            ['email' => 'parent@edukit.test', 'password' => 'Edukit@Parent2026!Secure', 'role' => 'parent'],
        ];
    }
}
