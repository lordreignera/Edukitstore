<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccountProvisioner;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $role = trim((string) $request->query('role'));
        $status = trim((string) $request->query('status'));
        $roles = $this->roleOptions();

        if ($role !== '' && ! array_key_exists($role, $roles)) {
            $role = '';
        }

        $users = User::with(['roles', 'creator'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($role !== '', fn ($query) => $query->role($role))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => $roles,
            'search' => $search,
            'selectedRole' => $role,
            'selectedStatus' => $status,
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'user' => new User(['is_active' => true]),
            'roles' => $this->manuallyCreatableRoles(),
        ]);
    }

    public function store(Request $request, AccountProvisioner $provisioner): RedirectResponse
    {
        $roles = $this->manuallyCreatableRoles();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', 'unique:suppliers,email', 'unique:drivers,email'],
            'role' => ['required', Rule::in(array_keys($roles))],
            'password' => ['required', 'confirmed', Password::defaults()],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user = $provisioner->provision($data['email'], $data['name'], $data['role'], auth()->id());
        $user->update([
            'password' => $data['password'],
            'is_active' => $request->boolean('is_active'),
            'must_change_password' => true,
        ]);

        return redirect()->route('admin.users.index')->with('status', 'User created with a temporary password. They will be asked to change it after signing in.');
    }

    public function edit(User $user): View
    {
        $this->ensureManageable($user);

        return view('admin.users.edit', [
            'user' => $user->load('roles'),
            'roles' => $this->roleOptions(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureManageable($user);
        $roles = $this->roleOptions();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user),
                Rule::unique('suppliers', 'email')->ignore($user->supplier?->id),
                Rule::unique('drivers', 'email')->ignore($user->driver?->id),
            ],
            'role' => ['required', Rule::in(array_keys($roles))],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $active = $request->boolean('is_active');
        if ($user->is(auth()->user()) && (! $active || ! $user->hasRole($data['role']))) {
            return back()->withErrors(['role' => 'You cannot deactivate your own account or change your own role.'])->withInput();
        }

        if ($user->supplier && $data['role'] !== 'supplier') {
            return back()->withErrors(['role' => 'This account is linked to a supplier and must keep the Supplier role.'])->withInput();
        }

        if ($user->driver && $data['role'] !== 'delivery-person') {
            return back()->withErrors(['role' => 'This account is linked to a delivery partner and must keep the Delivery Partner role.'])->withInput();
        }

        if ($data['role'] === 'supplier' && ! $user->supplier) {
            return back()->withErrors(['role' => 'Create supplier accounts by approving a supplier record.'])->withInput();
        }

        if ($data['role'] === 'delivery-person' && ! $user->driver) {
            return back()->withErrors(['role' => 'Create delivery-partner accounts by approving a delivery partner record.'])->withInput();
        }

        $this->preventLastSuperAdminDeactivation($user, $active);

        DB::transaction(function () use ($user, $data, $active) {
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'is_active' => $active,
            ]);
            $user->syncRoles([$data['role']]);
            $this->synchronizeLinkedProfiles($user, $active, $data['name'], $data['email']);

            if (! $active) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
        });

        return redirect()->route('admin.users.index')->with('status', 'User account updated.');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        $this->ensureManageable($user);

        if ($user->is(auth()->user())) {
            return back()->withErrors(['user' => 'You cannot deactivate your own account.']);
        }

        $active = ! $user->is_active;
        $this->preventLastSuperAdminDeactivation($user, $active);
        DB::transaction(function () use ($user, $active) {
            $user->update(['is_active' => $active]);
            $this->synchronizeLinkedProfiles($user, $active, $user->name, $user->email);
        });

        if (! $active) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        return back()->with('status', $active ? 'User account activated.' : 'User account deactivated.');
    }

    public function sendPasswordLink(User $user, AccountProvisioner $provisioner): RedirectResponse
    {
        $this->ensureManageable($user);

        if (! $user->is_active) {
            return back()->withErrors(['user' => 'Activate this account before sending a password setup link.']);
        }

        $message = $provisioner->sendSetupLink($user)
            ? 'Password setup link sent.'
            : 'The password setup link could not be sent. Check the mail configuration.';

        return back()->with('status', $message);
    }

    private function ensureManageable(User $user): void
    {
        abort_if($user->hasAnyRole(['super-admin', 'admin', 'finance-admin']) && ! auth()->user()->hasRole('super-admin'), 403);
    }

    private function preventLastSuperAdminDeactivation(User $user, bool $active): void
    {
        if (! $active && $user->hasRole('super-admin') && User::role('super-admin')->where('is_active', true)->count() <= 1) {
            abort(422, 'The final active super administrator cannot be deactivated.');
        }
    }

    private function roleOptions(): array
    {
        $roles = [
            'admin' => 'EduKit Administrator',
            'finance-admin' => 'Finance Administrator',
            'supplier' => 'Supplier',
            'delivery-person' => 'Delivery Partner',
            'school-admin' => 'School Administrator',
            'school-receiver' => 'School Receiver',
            'parent' => 'Parent / Customer',
        ];

        if (auth()->user()->hasRole('super-admin')) {
            $roles = ['super-admin' => 'Super Administrator'] + $roles;
        } else {
            unset($roles['admin'], $roles['finance-admin']);
        }

        return $roles;
    }

    private function manuallyCreatableRoles(): array
    {
        return array_diff_key($this->roleOptions(), array_flip(['supplier', 'delivery-person']));
    }

    private function synchronizeLinkedProfiles(User $user, bool $active, string $name, string $email): void
    {
        if ($user->supplier) {
            $user->supplier->update([
                'email' => $email,
                'is_active' => $active && $user->supplier->is_approved,
            ]);
        }

        if ($user->driver) {
            $user->driver->update([
                'name' => $name,
                'email' => $email,
                'is_available' => $active ? $user->driver->is_available : false,
            ]);
        }
    }
}
