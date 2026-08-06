<?php

use Illuminate\Support\Facades\Route as RouteFacade;

RouteFacade::post('handheld/hh-login', function (\Illuminate\Http\Request $request) {
    $request->validate([
        'usercode' => 'required|numeric',
        'password' => 'required',
    ]);

    $user = \App\Models\User::where('usercode', $request->usercode)->first();

    if (!$user || !\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'بيانات الدخول غير صحيحة'], 401);
    }

    if (!$user->is_active) {
        return response()->json(['message' => 'الحساب معطّل'], 403);
    }

    $user->tokens()->delete();
    $token = $user->createToken('hh-token')->plainTextToken;

    $defaultBranch = $user->branches()->wherePivot('is_default', true)->first();

    return response()->json([
        'token' => $token,
        'user' => [
            'id' => $user->id,
            'usercode' => $user->usercode,
            'name' => $user->name,
            'company_id' => $user->company_id,
            'branch_id' => $defaultBranch?->id,
        ],
    ]);
});
