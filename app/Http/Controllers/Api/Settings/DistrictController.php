<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\District;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class DistrictController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = District::with($with);
        if ($request->trashed) {
            $query->onlyTrashed();
        }
        if ($request->city_id) {
            $query->where('city_id', $request->city_id);
        }
        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('district', 'store'));
        $district = District::create($data);

        return response()->json($district, 201);
    }

    public function show(District $district)
    {
        return $district;
    }

    public function update(Request $request, District $district)
    {
        $data = $request->validate(ValidationRules::for('district', 'update', $district));
        $district->update($data);

        return response()->json($district);
    }

    public function destroy(District $district)
    {
        $district->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $district = District::onlyTrashed()->findOrFail($id);
        $district->restore();

        return response()->json($district);
    }

    public function forceDelete(int $id)
    {
        $district = District::onlyTrashed()->findOrFail($id);
        $district->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('district', 'store');
    }
}
