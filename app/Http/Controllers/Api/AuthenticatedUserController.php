<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthenticatedUserController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $roles = $user->roles()->pluck('name')->all();
        $permissions = $user->roles()
            ->with('permissions:id,name')
            ->get()
            ->flatMap(fn ($role) => $role->permissions->pluck('name'))
            ->unique()
            ->values()
            ->all();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
            ],
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }
}
