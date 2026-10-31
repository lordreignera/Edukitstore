<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AccountProvisioner
{
    public function createPending(string $email, string $name, string $password, string|array $roles): User
    {
        return DB::transaction(function () use ($email, $name, $password, $roles) {
            $normalizedEmail = Str::lower($email);
            $roleNames = $this->normalizeRoles($roles);

            if (User::where('email', $normalizedEmail)->exists()) {
                throw ValidationException::withMessages(['email' => 'This email already belongs to an EduKit account.']);
            }

            foreach ($roleNames as $roleName) {
                Role::findOrCreate($roleName, 'web');
            }
            $user = User::create([
                'email' => $normalizedEmail,
                'name' => $name,
                'password' => $password,
                'email_verified_at' => null,
                'is_active' => false,
            ]);
            $user->syncRoles($roleNames);

            return $user;
        });
    }

    public function provision(string $email, string $name, string|array $roles, ?int $createdBy, ?int $linkedUserId = null): User
    {
        return DB::transaction(function () use ($email, $name, $roles, $createdBy, $linkedUserId) {
            $normalizedEmail = Str::lower($email);
            $roleNames = $this->normalizeRoles($roles);
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

            foreach ($roleNames as $roleName) {
                Role::findOrCreate($roleName, 'web');
            }
            $user->syncRoles($roleNames);

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

    /**
     * @param string|array<int, string> $roles
     * @return array<int, string>
     */
    private function normalizeRoles(string|array $roles): array
    {
        return collect(Arr::wrap($roles))
            ->map(fn ($role): string => trim((string) $role))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
