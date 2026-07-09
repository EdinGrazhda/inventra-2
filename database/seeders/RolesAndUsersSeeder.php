<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Support\AccessControl;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolesAndUsersSeeder extends Seeder
{
    public function run(): void
    {
        AccessControl::seedDefaults();

        $adminEmail = env('SEED_ADMIN_EMAIL', 'admin@example.com');
        $adminPassword = env('SEED_ADMIN_PASSWORD');

        $admin = User::query()->firstOrNew(['email' => $adminEmail]);

        $admin->name = env('SEED_ADMIN_NAME', 'Admin User');
        $admin->email_verified_at ??= now();

        if (! $admin->exists || $adminPassword) {
            $admin->password = Hash::make($adminPassword ?: 'password');
        }

        $admin->save();

        $superAdminRole = Role::query()->where('name', 'super_admin')->firstOrFail();

        $admin->roles()->syncWithoutDetaching([$superAdminRole->id]);
    }
}
