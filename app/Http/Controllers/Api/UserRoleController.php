<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserRoleController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $user = User::findOrFail($userId);
        $roles = $user->roles()->get(['roles.id', 'roles.name', 'roles.code', 'roles.description']);

        return response()->json([
            'user_id' => $user->id,
            'roles' => $roles,
        ]);
    }

    public function update(Request $request, string $userId)
    {
        $user = User::findOrFail($userId);

        $request->validate([
            'role_ids' => 'required|array',
            'role_ids.*' => 'integer|exists:roles,id',
        ]);

        $user->roles()->sync($request->role_ids);

        $roles = $user->roles()->get(['roles.id', 'roles.name', 'roles.code', 'roles.description']);

        return response()->json([
            'user_id' => $user->id,
            'roles' => $roles,
        ]);
    }
}
