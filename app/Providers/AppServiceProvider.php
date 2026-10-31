<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Login;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Gate::before(function (?User $user): ?bool {
            return $user?->hasRole('super-admin') ? true : null;
        });

        Event::listen(Login::class, function (Login $event): void {
            if ($event->guard === 'web' && $event->user instanceof User) {
                $event->user->forceFill(['last_login_at' => now()])->save();
            }
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if ($event->guard !== 'web' || ! $event->user) {
                return;
            }

            if (config('session.driver') === 'database') {
                DB::connection(config('session.connection'))
                    ->table(config('session.table', 'sessions'))
                    ->where('user_id', $event->user->getAuthIdentifier())
                    ->delete();
            }

            $event->user->tokens()->delete();
        });

    }
}
