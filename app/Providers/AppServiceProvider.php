<?php

namespace App\Providers;

use App\Models\User;
use App\Support\SuperAdministratorProtection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Events\Logout;

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
