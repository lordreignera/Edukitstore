<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AccountProvisioner
{
    public function createPending(string $email, string $name, string $password, string $role): User
    {
        return DB::transaction(function () use ($email, $name, $password, $role) {
            $normalizedEmail = Str::lower($email);

            if (User::where('email', $normalizedEmail)->exists()) {
                throw ValidationException::withMessages(['email' => 'This email already belongs to an EduKit account.']);
            }

            Role::findOrCreate($role);
            $user = User::create([
                'email' => $normalizedEmail,
                'name' => $name,
                'password' => $password,
                'email_verified_at' => null,
                'is_active' => false,
            ]);
            $user->syncRoles([$role]);

            return $user;
        });
    }

    public function provision(string $email, string $name, string $role, ?int $createdBy, ?int $linkedUserId = null): User
    {
        return DB::transaction(function () use ($email, $name, $role, $createdBy, $linkedUserId) {
            $normalizedEmail = Str::lower($email);
            $user = User::where('email', $normalizedEmail)->first();

            if ($user && $user->id !== $linkedUserId) {
                throw ValidationException::withMessages([
                    'email' => 'This email already belongs to another EduKit account.',
                ]);
            }

            if (! $user) {
                $user = User::create([
                    'email' => $normalizedEmail,
                    'name' => $name,
                    'password' => Str::password(32),
                    'email_verified_at' => now(),
                    'is_active' => true,
                    'created_by' => $createdBy,
                ]);
            }

            if (! $user->is_active) {
                $user->update(['is_active' => true]);
            }

            $user->forceFill([
                'email_verified_at' => $user->email_verified_at ?? now(),
                'created_by' => $user->created_by ?? $createdBy,
            ])->save();

            Role::findOrCreate($role);
            $user->syncRoles([$role]);

            return $user;
        });
    }

    public function sendSetupLink(User $user): bool
    {
        try {
            return Password::sendResetLink(['email' => $user->email]) === Password::RESET_LINK_SENT;
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }
    }
}
