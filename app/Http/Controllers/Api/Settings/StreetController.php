<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Models\Street;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class StreetController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = Street::with($with);
        if ($request->trashed) {
            $query->onlyTrashed();
        }
        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('street', 'store'));
        $street = Street::create($data);

        return response()->json($street, 201);
    }

    public function show(Street $street)
    {
        return $street;
    }

    public function update(Request $request, Street $street)
    {
        $data = $request->validate(ValidationRules::for('street', 'update', $street));
        $street->update($data);

        return response()->json($street);
    }

    public function destroy(Street $street)
    {
        $street->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $street = Street::onlyTrashed()->findOrFail($id);
        $street->restore();

        return response()->json($street);
    }

    public function forceDelete(int $id)
    {
        $street = Street::onlyTrashed()->findOrFail($id);
        $street->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('street', 'store');
    }
}
