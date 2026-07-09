<?php

use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use App\Support\AccessControl;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('api routes require authentication', function () {
    $this->getJson('/api/user')
        ->assertUnauthorized();
});

test('api user route exposes safe authenticated context', function () {
    AccessControl::seedDefaults();

    $user = User::factory()->create();
    $user->roles()->attach(Role::where('name', 'staff')->firstOrFail());

    $this->actingAs($user)
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('user.email', $user->email)
        ->assertJsonMissingPath('user.password')
        ->assertJsonPath('roles.0', 'staff');
});

test('api lookups require matching permissions', function () {
    AccessControl::seedDefaults();

    Category::query()->create(['name' => 'Allowed category', 'is_active' => true]);

    $staff = User::factory()->create();
    $staff->roles()->attach(Role::where('name', 'staff')->firstOrFail());

    $this->actingAs($staff)
        ->getJson('/api/inventory/lookups/categories')
        ->assertForbidden();

    $admin = User::factory()->create();
    $admin->roles()->attach(Role::where('name', 'admin')->firstOrFail());

    $this->actingAs($admin)
        ->getJson('/api/inventory/lookups/categories')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Allowed category');
});

test('security headers are sent on web and api responses', function () {
    $this->get('/')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

    $this->getJson('/api/user')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY');
});
