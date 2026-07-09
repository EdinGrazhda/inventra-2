<?php

use App\Models\Role;
use App\Models\User;
use App\Support\AccessControl;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('staff can access permitted inventory areas only', function () {
    AccessControl::seedDefaults();

    $user = User::factory()->create();
    $user->roles()->attach(Role::where('name', 'staff')->firstOrFail());

    $this->actingAs($user);

    $this->get(route('stock.index'))->assertOk();
    $this->get(route('sales.index'))->assertOk();
    $this->get(route('users-roles.index'))->assertForbidden();
    $this->get(route('reports.index'))->assertForbidden();
});

test('admins can manage users and roles', function () {
    AccessControl::seedDefaults();

    $user = User::factory()->create();
    $user->roles()->attach(Role::where('name', 'admin')->firstOrFail());

    $this->actingAs($user)
        ->get(route('users-roles.index'))
        ->assertOk();
});
