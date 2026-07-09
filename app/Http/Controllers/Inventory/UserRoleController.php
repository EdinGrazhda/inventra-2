<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Support\AccessControl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserRoleController extends Controller
{
    public function index(Request $request): Response
    {
        $this->ensureDefaultRoles();

        $search = $request->string('search')->toString();

        $records = User::query()
            ->with('roles:id,name,label')
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
            ->latest()
            ->paginate(10)
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role_id' => $user->roles->first()?->id,
                'role_name' => $user->roles->pluck('label')->filter()->join(', ') ?: $user->roles->pluck('name')->join(', '),
                'email_verified_at' => $user->email_verified_at?->toDateTimeString(),
                'created_at' => $user->created_at?->toDateTimeString(),
            ])
            ->withQueryString();

        return Inertia::render('inventory/crud/index', [
            'module' => $this->module(),
            'records' => $records,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        $this->ensureDefaultRoles();

        return Inertia::render('inventory/crud/form', [
            'module' => $this->module(),
            'record' => null,
            'options' => $this->options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $roleId = $data['role_id'] ?? null;
        unset($data['role_id']);

        $user = User::create($data);
        $user->roles()->sync($roleId ? [$roleId] : []);

        return to_route('users-roles.index')->with('success', 'User created.');
    }

    public function show(User $user): Response
    {
        $user->load('roles:id,name,label');

        return Inertia::render('inventory/crud/show', [
            'module' => $this->module(),
            'record' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role_id' => $user->roles->first()?->id,
                'role_name' => $user->roles->pluck('label')->filter()->join(', ') ?: $user->roles->pluck('name')->join(', '),
                'email_verified_at' => $user->email_verified_at?->toDateTimeString(),
                'created_at' => $user->created_at?->toDateTimeString(),
            ],
        ]);
    }

    public function edit(User $user): Response
    {
        $this->ensureDefaultRoles();
        $user->load('roles:id');

        return Inertia::render('inventory/crud/form', [
            'module' => $this->module(),
            'record' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'password' => '',
                'role_id' => $user->roles->first()?->id,
            ],
            'options' => $this->options(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user->id);
        $roleId = $data['role_id'] ?? null;
        unset($data['role_id']);

        if (($data['password'] ?? '') === '') {
            unset($data['password']);
        }

        $user->update($data);
        $user->roles()->sync($roleId ? [$roleId] : []);

        return to_route('users-roles.index')->with('success', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->roles()->detach();
        $user->delete();

        return to_route('users-roles.index')->with('success', 'User deleted.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($ignoreId)],
            'password' => [$ignoreId ? 'nullable' : 'required', 'string', 'min:8'],
            'role_id' => ['nullable', 'exists:roles,id'],
        ]);
    }

    private function options(): array
    {
        return [
            'roles' => Role::query()->orderBy('name')->get(['id', 'name', 'label']),
        ];
    }

    private function ensureDefaultRoles(): void
    {
        AccessControl::seedDefaults();
    }

    private function module(): array
    {
        return [
            'name' => 'Users & Roles',
            'singular' => 'user',
            'route' => 'users-roles',
            'description' => 'Manage users and assign their inventory access role.',
            'fields' => [
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
                ['name' => 'password', 'label' => 'Password', 'type' => 'password'],
                ['name' => 'role_id', 'label' => 'Role', 'type' => 'select', 'optionsKey' => 'roles'],
            ],
            'columns' => [
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'email', 'label' => 'Email'],
                ['key' => 'role_name', 'label' => 'Role'],
                ['key' => 'email_verified_at', 'label' => 'Verified at'],
                ['key' => 'created_at', 'label' => 'Created'],
            ],
        ];
    }
}
