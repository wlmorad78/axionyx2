<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\Country;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = Country::with($with);
        if ($request->trashed) {
            $query->onlyTrashed();
        }
        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('country', 'store'));
        $country = Country::create($data);

        return response()->json($country, 201);
    }

    public function show(Country $country)
    {
        return $country;
    }

    public function update(Request $request, Country $country)
    {
        $data = $request->validate(ValidationRules::for('country', 'update', $country));
        $country->update($data);

        return response()->json($country);
    }

    public function destroy(Country $country)
    {
        $country->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $country = Country::onlyTrashed()->findOrFail($id);
        $country->restore();

        return response()->json($country);
    }

    public function forceDelete(int $id)
    {
        $country = Country::onlyTrashed()->findOrFail($id);
        $country->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('country', 'store');
    }
}
