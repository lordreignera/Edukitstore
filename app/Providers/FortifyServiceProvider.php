<?php

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Responses\RoleAwareLoginResponse;
use App\Http\Responses\RoleAwareTwoFactorLoginResponse;
use App\Mail\VerifyAccountEmailMail;
use App\Models\User;
use App\Support\DemoAccountAccess;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, RoleAwareLoginResponse::class);
        $this->app->singleton(TwoFactorLoginResponseContract::class, RoleAwareTwoFactorLoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        VerifyEmail::toMailUsing(function (User $user, string $verificationUrl): VerifyAccountEmailMail {
            return (new VerifyAccountEmailMail($user->name, $verificationUrl))
                ->to($user->email);
        });

        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        Password::defaults(fn () => Password::min(12)
            ->mixedCase()
            ->numbers()
            ->symbols());

        Fortify::authenticateUsing(function (Request $request) {
            $email = Str::lower((string) $request->input('email'));

            // A copied local database must not make documented demo credentials
            // valid online unless online demo access was explicitly enabled.
            if (! DemoAccountAccess::isAllowed($email)) {
                return null;
            }

            $user = User::where('email', $email)->first();

            if (! $user || ! $user->is_active || $user->hasRole('parent') || ! Hash::check((string) $request->input('password'), $user->password)) {
                return null;
            }

            return $user;
        });

        RateLimiter::for('login', function (Request $request) {
            $email = Str::transliterate(Str::lower((string) $request->input(Fortify::username())));

            return [
                Limit::perMinute(5)->by($email.'|'.$request->ip()),
                Limit::perMinute(10)->by('account:'.$email),
            ];
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->input('credential.id');

            return Limit::perMinute(10)->by(
                ($credentialId ?: $request->session()->getId()).'|'.$request->ip()
            );
        });
    }
}
