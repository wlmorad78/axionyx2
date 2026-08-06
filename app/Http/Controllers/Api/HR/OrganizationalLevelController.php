<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\OrganizationalLevel;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class OrganizationalLevelController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = OrganizationalLevel::with($with);
        if ($request->trashed) {
            $query->onlyTrashed();
        }
        return $query->orderBy('level_order')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('organizational_level', 'store'));
        $level = OrganizationalLevel::create($data);
        return response()->json($level, 201);
    }

    public function show(OrganizationalLevel $organizationalLevel)
    {
        return $organizationalLevel;
    }

    public function update(Request $request, OrganizationalLevel $organizationalLevel)
    {
        $data = $request->validate(ValidationRules::for('organizational_level', 'update', $organizationalLevel));
        $organizationalLevel->update($data);
        return response()->json($organizationalLevel);
    }

    public function destroy(OrganizationalLevel $organizationalLevel)
    {
        if ($organizationalLevel->is_system) {
            return response()->json(['message' => 'لا يمكن حذف مستوى نظام'], 403);
        }
        $organizationalLevel->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $level = OrganizationalLevel::onlyTrashed()->findOrFail($id);
        $level->restore();
        return response()->json($level);
    }

    public function forceDelete(int $id)
    {
        $level = OrganizationalLevel::onlyTrashed()->findOrFail($id);
        $level->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('organizational_level', 'store');
    }
}
