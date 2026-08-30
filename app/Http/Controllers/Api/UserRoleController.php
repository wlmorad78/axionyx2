<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Http\Request;

class UserRoleController extends Controller
{
    /**
     * عرض نوع المستخدم الحالي للمستخدم المحدد.
     */
    public function index(Request $request, string $userId)
    {
        $user = User::findOrFail($userId);

        return response()->json([
            'user_id' => $user->id,
            'user_type' => $user->userType,
        ]);
    }

    /**
     * تعيين نوع المستخدم (استبدال الأدوار بنوع المستخدم).
     */
    public function update(Request $request, string $userId)
    {
        $user = User::findOrFail($userId);

        $request->validate([
            'user_type_id' => 'required|integer|exists:user_types,id',
        ]);

        $user->update(['user_type_id' => $request->user_type_id]);

        return response()->json([
            'user_id' => $user->id,
            'user_type' => $user->fresh()->userType,
        ]);
    }
}
