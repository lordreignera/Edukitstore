<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccountProvisioner;
use App\Support\AccountAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

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
        $request->merge([
            'roles' => $request->input('roles', $request->filled('role') ? [$request->input('role')] : []),
        ]);
        if ($request->filled('role') && ! array_key_exists((string) $request->input('role'), $roles)) {
            return back()->withErrors(['role' => 'Create supplier and delivery accounts through their approval workflow.'])->withInput();
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', 'unique:suppliers,email', 'unique:drivers,email'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [Rule::in(array_keys($roles))],
            'password' => ['required', 'confirmed', Password::defaults()],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user = $provisioner->provision($data['email'], $data['name'], $data['roles'], auth()->id());
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
        $request->merge([
            'roles' => $request->input('roles', $request->filled('role') ? [$request->input('role')] : []),
        ]);
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
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [Rule::in(array_keys($roles))],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $active = $request->boolean('is_active');
        if ($user->is(auth()->user()) && (! $active || ! $user->hasExactRoles($data['roles']))) {
            return back()->withErrors(['roles' => 'You cannot deactivate your own account or change your own roles.'])->withInput();
        }

        if ($user->supplier && ! in_array('supplier', $data['roles'], true)) {
            return back()->withErrors(['roles' => 'This account is linked to a supplier and must keep the Supplier role.'])->withInput();
        }

        if ($user->driver && ! in_array('delivery-person', $data['roles'], true)) {
            return back()->withErrors(['roles' => 'This account is linked to a delivery partner and must keep the Delivery Partner role.'])->withInput();
        }

        if (in_array('supplier', $data['roles'], true) && ! $user->supplier) {
            return back()->withErrors(['roles' => 'Create supplier accounts by approving a supplier record.'])->withInput();
        }

        if (in_array('delivery-person', $data['roles'], true) && ! $user->driver) {
            return back()->withErrors(['roles' => 'Create delivery-partner accounts by approving a delivery partner record.'])->withInput();
        }

        $this->preventLastSuperAdminChange($user, $active, $data['roles']);

        DB::transaction(function () use ($user, $data, $active) {
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'is_active' => $active,
            ]);
            $user->syncRoles($data['roles']);
            $this->synchronizeLinkedProfiles($user, $active, $data['name'], $data['email']);

            if (! $active) {
                AccountAccess::revoke($user);
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
        $this->preventLastSuperAdminChange($user, $active, $user->getRoleNames()->all());
        DB::transaction(function () use ($user, $active) {
            $user->update(['is_active' => $active]);
            $this->synchronizeLinkedProfiles($user, $active, $user->name, $user->email);
            if (! $active) {
                AccountAccess::revoke($user);
            }
        });

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

    private function preventLastSuperAdminChange(User $user, bool $active, array $roles): void
    {
        if (
            $user->hasRole('super-admin')
            && (! $active || ! in_array('super-admin', $roles, true))
            && User::role('super-admin')->where('is_active', true)->count() <= 1
        ) {
            abort(422, 'The final active super administrator cannot be deactivated or removed.');
        }
    }

    private function roleOptions(): array
    {
        $roles = Role::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Role $role): array => [
                $role->name => str($role->name)->replace(['-', '_'], ' ')->title()->toString(),
            ])
            ->all();

        if (! auth()->user()->hasRole('super-admin')) {
            $roles = array_diff_key($roles, array_flip(['super-admin', 'admin', 'finance-admin']));
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
