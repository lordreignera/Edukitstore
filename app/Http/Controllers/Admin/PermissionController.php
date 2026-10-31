<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\PermissionCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        return view('admin.permissions.index', [
            'permissions' => Permission::query()
                ->where('guard_name', 'web')
                ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                ->withCount('roles')
                ->orderBy('name')
                ->paginate(25)
                ->withQueryString(),
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('admin.permissions.create', ['permission' => new Permission(['guard_name' => 'web'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        Permission::create($this->validatedData($request));

        return redirect()->route('admin.permissions.index')->with('status', 'Permission created.');
    }

    public function edit(Permission $permission): View
    {
        return view('admin.permissions.edit', compact('permission'));
    }

    public function update(Request $request, Permission $permission): RedirectResponse
    {
        $data = $this->validatedData($request, $permission);

        if (in_array($permission->name, PermissionCatalog::names(), true) && $data['name'] !== $permission->name) {
            return back()->withErrors(['name' => 'System permission names cannot be changed because application routes use them.'])->withInput();
        }

        $permission->update(['name' => $data['name']]);

        return redirect()->route('admin.permissions.index')->with('status', 'Permission updated.');
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        if (in_array($permission->name, PermissionCatalog::names(), true)) {
            return back()->withErrors(['permission' => 'System permissions cannot be deleted.']);
        }

        $permission->roles()->detach();
        $permission->users()->detach();
        $permission->delete();

        return back()->with('status', 'Permission deleted.');
    }

    /**
     * @return array{name: string, guard_name: string}
     */
    private function validatedData(Request $request, ?Permission $permission = null): array
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                'regex:/^[a-z0-9][a-z0-9._-]*$/',
                Rule::unique('permissions', 'name')
                    ->where(fn ($query) => $query->where('guard_name', 'web'))
                    ->ignore($permission),
            ],
        ]);

        return [
            'name' => strtolower(trim($data['name'])),
            'guard_name' => 'web',
        ];
    }
}
