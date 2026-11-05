<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;
use Throwable;

class RegistrationController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.customer-register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255', 'unique:users,email',
                'unique:suppliers,email', 'unique:drivers,email',
            ],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
        ]);

        $customer = DB::transaction(function () use ($data): User {
            $role = Role::findOrCreate('customer', 'web');
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'is_active' => true,
                'must_change_password' => false,
            ]);
            $user->assignRole($role);

            return $user;
        });

        Auth::login($customer);
        $request->session()->regenerate();
        $verificationSent = false;

        try {
            $customer->sendEmailVerificationNotification();
            $verificationSent = true;
        } catch (Throwable $exception) {
            Log::warning('Customer email verification notification could not be sent.', [
                'user_id' => $customer->id,
                'exception' => $exception::class,
            ]);
        }

        return redirect()->route('verification.notice')
            ->with('status', $verificationSent
                ? 'Your account is ready. We sent a verification link to your email. Verify it to view orders linked to that address. You can still place and pay for orders now.'
                : 'Your account is ready, but we could not send the verification email just now. Use Resend Verification Email below to try again. You can still place and pay for orders as a guest.');
    }
}
