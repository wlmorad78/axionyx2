<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\Governorate;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class GovernorateController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = Governorate::with($with);
        if ($request->trashed) {
            $query->onlyTrashed();
        }
        if ($request->country_id) {
            $query->where('country_id', $request->country_id);
        }
        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('governorate', 'store'));
        $governorate = Governorate::create($data);

        return response()->json($governorate, 201);
    }

    public function show(Governorate $governorate)
    {
        return $governorate;
    }

    public function update(Request $request, Governorate $governorate)
    {
        $data = $request->validate(ValidationRules::for('governorate', 'update', $governorate));
        $governorate->update($data);

        return response()->json($governorate);
    }

    public function destroy(Governorate $governorate)
    {
        $governorate->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $governorate = Governorate::onlyTrashed()->findOrFail($id);
        $governorate->restore();

        return response()->json($governorate);
    }

    public function forceDelete(int $id)
    {
        $governorate = Governorate::onlyTrashed()->findOrFail($id);
        $governorate->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('governorate', 'store');
    }
}
