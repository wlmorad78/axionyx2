<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\Currency;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = Currency::with($with);
        if ($request->trashed) {
            $query->onlyTrashed();
        }
        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('currency', 'store'));
        $currency = Currency::create($data);

        return response()->json($currency, 201);
    }

    public function show(Currency $currency)
    {
        return $currency;
    }

    public function update(Request $request, Currency $currency)
    {
        $data = $request->validate(ValidationRules::for('currency', 'update', $currency));
        $currency->update($data);

        return response()->json($currency);
    }

    public function destroy(Currency $currency)
    {
        $currency->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $currency = Currency::onlyTrashed()->findOrFail($id);
        $currency->restore();

        return response()->json($currency);
    }

    public function forceDelete(int $id)
    {
        $currency = Currency::onlyTrashed()->findOrFail($id);
        $currency->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('currency', 'store');
    }
}
