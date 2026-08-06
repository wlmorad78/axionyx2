<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\City;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = City::with($with);
        if ($request->trashed) {
            $query->onlyTrashed();
        }
        if ($request->governorate_id) {
            $query->where('governorate_id', $request->governorate_id);
        }
        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('city', 'store'));
        $city = City::create($data);

        return response()->json($city, 201);
    }

    public function show(City $city)
    {
        return $city;
    }

    public function update(Request $request, City $city)
    {
        $data = $request->validate(ValidationRules::for('city', 'update', $city));
        $city->update($data);

        return response()->json($city);
    }

    public function destroy(City $city)
    {
        $city->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $city = City::onlyTrashed()->findOrFail($id);
        $city->restore();

        return response()->json($city);
    }

    public function forceDelete(int $id)
    {
        $city = City::onlyTrashed()->findOrFail($id);
        $city->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('city', 'store');
    }
}
