<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'usercode' => 'required|numeric',
            'password' => 'required',
        ]);

        $user = User::where('usercode', $request->usercode)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'usercode' => ['بيانات الدخول غير صحيحة.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'usercode' => ['حسابك معطّل. تواصل مع المسؤول.'],
            ]);
        }

        $token = null;
        $lastException = null;
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                $user->tokens()->delete();
                $token = $user->createToken('auth-token')->plainTextToken;
                break;
            } catch (\Exception $e) {
                $lastException = $e;
                if ($attempt < 3) {
                    usleep(500000);
                    continue;
                }
            }
        }

        if (!$token) {
            throw $lastException ?? new \Exception('فشل تسجيل الدخول.');
        }

        $company = $user->company;
        $subscription = $company
            ? $company->subscriptions()->where('status', 'active')->with('plan')->first()
            : null;

        $branch = $user->branches()->first();

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'usercode' => $user->usercode,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'company_id' => $user->company_id,
            ],
            'company' => $company ? [
                'id' => $company->id,
                'code' => $company->code,
                'name' => $company->name_ar ?? $company->name_en ?? $company->code,
                'name_ar' => $company->name_ar,
                'name_en' => $company->name_en,
                'phone' => $company->phone,
                'email' => $company->email,
            ] : null,
            'branch' => $branch ? [
                'id' => $branch->id,
                'name' => $branch->name ?? $branch->name_ar ?? '',
            ] : null,
            'plan' => $subscription?->plan ? [
                'id' => $subscription->plan->id,
                'code' => $subscription->plan->code,
                'name' => $subscription->plan->name,
                'tier' => $subscription->plan->tier,
                'monthly_price' => $subscription->plan->monthly_price,
                'max_users' => $subscription->plan->max_users,
                'max_branches' => $subscription->plan->max_branches,
                'max_warehouses' => $subscription->plan->max_warehouses,
            ] : null,
            'modules' => $subscription?->plan
                ? $subscription->plan->modules->map(fn($m) => [
                    'key' => $m->key,
                    'title' => $m->title,
                    'can_view' => (bool) $m->pivot->can_view,
                    'can_create' => (bool) $m->pivot->can_create,
                    'can_edit' => (bool) $m->pivot->can_edit,
                    'can_delete' => (bool) $m->pivot->can_delete,
                ])
                : collect(),
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $accessToken = $user->currentAccessToken();
            if (method_exists($accessToken, 'delete')) {
                $accessToken->delete();
            } else {
                $user->tokens()->delete();
            }
        }

        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('companies');
        $user->accessible_companies = $user->accessibleCompanies();
        return response()->json($user);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|confirmed|min:8',
        ]);

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['message' => 'Password changed successfully']);
    }

    public function register(Request $request)
    {
        $request->validate([
            'usercode' => 'required|numeric|unique:users',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'password' => 'required|confirmed|min:8',
        ]);

        $user = User::create([
            'usercode' => $request->usercode,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }
}
