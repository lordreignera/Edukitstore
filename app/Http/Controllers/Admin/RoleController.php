<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    private const SYSTEM_ROLES = ['super-admin', 'admin', 'finance-admin'];

    public function index(): View
    {
        $rolesTable = (new Role)->getTable();
        $roleAssignmentsTable = config('permission.table_names.model_has_roles');
        $rolePivotKey = config('permission.column_names.role_pivot_key') ?: 'role_id';

        return view('admin.roles.index', [
            'roles' => Role::query()
                ->select("{$rolesTable}.*")
                ->addSelect([
                    'users_count' => DB::table("{$roleAssignmentsTable} as role_assignments")
                        ->selectRaw('COUNT(*)')
                        ->whereColumn("role_assignments.{$rolePivotKey}", "{$rolesTable}.id")
                        ->where('role_assignments.model_type', (new User)->getMorphClass()),
                ])
                ->where('guard_name', 'web')
                ->withCount('permissions')
                ->orderBy('name')
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.roles.create', [
            'role' => new Role(['guard_name' => 'web']),
            'permissions' => $this->permissionGroups(),
            'selectedPermissions' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);

        if (in_array($data['name'], self::SYSTEM_ROLES, true)) {
            return back()->withErrors(['name' => 'That name is reserved for a system role.'])->withInput();
        }

        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('admin.roles.index')->with('status', 'Role created and permissions assigned.');
    }

    public function edit(Role $role): View
    {
        $this->ensureManageable($role);

        return view('admin.roles.edit', [
            'role' => $role->load('permissions'),
            'permissions' => $this->permissionGroups(),
            'selectedPermissions' => $role->permissions->pluck('name')->all(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->ensureManageable($role);
        $data = $this->validatedData($request, $role);

        if (in_array($role->name, self::SYSTEM_ROLES, true) && $data['name'] !== $role->name) {
            return back()->withErrors(['name' => 'System role names cannot be changed.'])->withInput();
        }

        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('admin.roles.index')->with('status', 'Role updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->ensureManageable($role);

        if (in_array($role->name, self::SYSTEM_ROLES, true)) {
            return back()->withErrors(['role' => 'System roles cannot be deleted.']);
        }

        $roleAssignmentsTable = config('permission.table_names.model_has_roles');
        $rolePivotKey = config('permission.column_names.role_pivot_key') ?: 'role_id';

        if (DB::table($roleAssignmentsTable)->where($rolePivotKey, $role->getKey())->exists()) {
            return back()->withErrors(['role' => 'Remove this role from its users before deleting it.']);
        }

        $role->delete();

        return back()->with('status', 'Role deleted.');
    }

    /**
     * @return array<string, \Illuminate\Support\Collection<int, Permission>>
     */
    private function permissionGroups(): array
    {
        return Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Permission $permission): string => PermissionCatalog::group($permission->name))
            ->all();
    }

    private function ensureManageable(Role $role): void
    {
        abort_if(
            in_array($role->name, self::SYSTEM_ROLES, true)
            && ! auth()->user()->hasRole('super-admin'),
            403
        );
    }

    /**
     * @return array{name: string, permissions?: array<int, string>}
     */
    private function validatedData(Request $request, ?Role $role = null): array
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                'regex:/^[a-z0-9][a-z0-9._-]*$/',
                Rule::unique('roles', 'name')
                    ->where(fn ($query) => $query->where('guard_name', 'web'))
                    ->ignore($role),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::exists('permissions', 'name')->where(fn ($query) => $query->where('guard_name', 'web'))],
        ]);

        $data['name'] = strtolower(trim($data['name']));

        return $data;
    }
}
