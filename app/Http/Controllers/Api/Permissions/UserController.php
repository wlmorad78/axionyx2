<?php

namespace App\Http\Controllers\Api\Permissions;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->with('roles');

        $with = $request->with ? explode(',', $request->with) : [];
        if ($with) {
            $query->with($with);
        }

        $companyId = $request->header('X-Company-Id')
            ?? $request->user()?->company_id;

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('usercode', 'like', "%{$search}%");
            });
        }

        if ($request->has('roles')) {
            $roles = $request->input('roles');
            if (is_string($roles)) $roles = explode(',', $roles);
            $query->whereHas('roles', function ($rq) use ($roles) {
                $rq->whereIn('name', $roles);
            });
        }

        if ($request->has('trashed') && $request->trashed) {
            $query->onlyTrashed();
        }

        $perPage = $request->input('per_page', 15);
        $users = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:4',
            'phone' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
            'usercode' => 'nullable|integer',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'integer|exists:roles,id',
        ]);

        $companyId = $request->header('X-Company-Id')
            ?? $request->user()?->company_id;

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->input('phone'),
            'is_active' => $request->boolean('is_active', true),
            'company_id' => $companyId,
            'usercode' => $request->input('usercode'),
        ]);

        if ($request->has('role_ids')) {
            $user->roles()->sync($request->role_ids);
        }

        $user->load('roles');

        return response()->json($user, 201);
    }

    public function show($id)
    {
        $user = User::with('roles')->findOrFail($id);
        return response()->json($user);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => "sometimes|email|unique:users,email,{$id}",
            'password' => 'sometimes|min:4',
            'phone' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
            'usercode' => 'nullable|integer',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'integer|exists:roles,id',
        ]);

        $data = $request->only(['name', 'email', 'phone', 'is_active', 'usercode']);
        if ($request->has('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        if ($request->has('role_ids')) {
            $user->roles()->sync($request->role_ids);
        }

        $user->load('roles');

        return response()->json($user);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'User deleted']);
    }

    public function schema()
    {
        return response()->json([
            'columns' => ['id', 'name', 'email', 'created_at', 'updated_at'],
        ]);
    }

    public function restore($id)
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();
        return response()->json($user);
    }

    public function forceDelete($id)
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->forceDelete();
        return response()->json(['message' => 'User permanently deleted']);
    }

    public function nextCode()
    {
        $last = User::orderBy('usercode', 'desc')->first();
        $next = $last ? intval($last->usercode) + 1 : User::FIRST_USERCODE;
        return response()->json(['code' => $next]);
    }
}
