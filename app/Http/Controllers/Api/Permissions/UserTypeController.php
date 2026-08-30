<?php

namespace App\Http\Controllers\Api\Permissions;

use App\Http\Controllers\Controller;
use App\Models\UserType;
use Illuminate\Http\Request;

class UserTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = UserType::query();
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }
        $data = $query->orderBy('name_ar')->get();

        return response()->json(['data' => $data]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'code' => 'required|string|max:50|unique:user_types,code,' . ($request->company_id ?? ''),
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $userType = UserType::create($validated);

        return response()->json($userType, 201);
    }

    public function show(UserType $userType)
    {
        return response()->json($userType);
    }

    public function update(Request $request, UserType $userType)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:user_types,code,' . $userType->id . ',id',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $userType->update($validated);

        return response()->json($userType);
    }

    public function destroy(UserType $userType)
    {
        if ($userType->is_protected) {
            return response()->json(['message' => 'لا يمكن حذف نوع مستخدم محمي'], 422);
        }
        if ($userType->users()->count() > 0) {
            return response()->json(['message' => 'لا يمكن حذف نوع مستخدم مرتبط بمستخدمين'], 422);
        }

        $userType->delete();
        return response()->json(null, 204);
    }
}
