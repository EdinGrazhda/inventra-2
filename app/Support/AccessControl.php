<?php

namespace App\Support;

use App\Models\Permission;
use App\Models\Role;

class AccessControl
{
    public const ROLES = [
        'super_admin' => 'Super Admin',
        'admin' => 'Admin',
        'moderator' => 'Moderator',
        'manager' => 'Manager',
        'staff' => 'Staff',
    ];

    public const PERMISSIONS = [
        'products.access' => 'Access products',
        'categories.access' => 'Access categories',
        'suppliers.access' => 'Access suppliers',
        'warehouses.access' => 'Access warehouses',
        'stock.access' => 'Access product stock',
        'stock_movements.access' => 'Access stock movements',
        'stock_transfers.access' => 'Access stock transfers',
        'purchases.access' => 'Access purchases',
        'sales.access' => 'Access sales',
        'reports.access' => 'Access reports',
        'users_roles.access' => 'Manage users and roles',
    ];

    public const ROLE_PERMISSIONS = [
        'super_admin' => ['*'],
        'admin' => [
            'products.access',
            'categories.access',
            'suppliers.access',
            'warehouses.access',
            'stock.access',
            'stock_movements.access',
            'stock_transfers.access',
            'purchases.access',
            'sales.access',
            'reports.access',
            'users_roles.access',
        ],
        'moderator' => [
            'products.access',
            'categories.access',
            'suppliers.access',
            'warehouses.access',
            'stock.access',
            'stock_movements.access',
            'stock_transfers.access',
            'purchases.access',
            'sales.access',
            'reports.access',
        ],
        'manager' => [
            'products.access',
            'categories.access',
            'suppliers.access',
            'warehouses.access',
            'stock.access',
            'stock_movements.access',
            'stock_transfers.access',
            'purchases.access',
            'sales.access',
            'reports.access',
        ],
        'staff' => [
            'products.access',
            'stock.access',
            'stock_movements.access',
            'sales.access',
        ],
    ];

    public static function seedDefaults(): void
    {
        $permissions = collect(self::PERMISSIONS)
            ->mapWithKeys(fn (string $label, string $name) => [
                $name => Permission::query()->updateOrCreate(['name' => $name], ['label' => $label]),
            ]);

        foreach (self::ROLES as $name => $label) {
            $role = Role::query()->updateOrCreate(['name' => $name], ['label' => $label]);
            $permissionNames = self::ROLE_PERMISSIONS[$name] ?? [];

            $role->permissions()->sync(
                in_array('*', $permissionNames, true)
                    ? $permissions->pluck('id')->all()
                    : $permissions->only($permissionNames)->pluck('id')->all()
            );
        }
    }
}
