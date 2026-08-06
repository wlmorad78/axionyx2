<?php

namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\Controller;
use App\Models\Pricing\PriceList;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class PriceListController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];

        $query = PriceList::with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")
                    ->orWhere('code', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('price_list', 'store'));

        return response()->json(PriceList::create($data), 201);
    }

    public function show(PriceList $price_list)
    {
        return $price_list->load(['company', 'itemPrices']);
    }

    public function update(Request $request, PriceList $price_list)
    {
        $data = $request->validate(ValidationRules::for('price_list', 'update', $price_list));

        $price_list->update($data);

        return response()->json($price_list);
    }

    public function destroy(PriceList $price_list)
    {
        if ($price_list->is_default) {
            return response()->json(['message' => 'Ù„Ø§ ÙŠÙ…ÙƒÙ† Ø­Ø°Ù Ø§Ù„Ù‚Ø§Ø¦Ù…Ø© Ø§Ù„Ø§ÙØªØ±Ø§Ø¶ÙŠØ©'], 403);
        }

        $price_list->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = PriceList::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        PriceList::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('price_list', 'store');
    }

    public function nextCode(Request $request)
    {
        $query = PriceList::withTrashed()
            ->where('code', 'like', 'PL-%');

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        $last = $query->get()
            ->filter(fn($item) => preg_match('/^PL-\d{5}$/', $item->code))
            ->map(fn($item) => (int) preg_replace('/\D/', '', $item->code))
            ->filter(fn($num) => $num > 0)
            ->max();

        $next = ($last ?? 0) + 1;

        return response()->json(['code' => 'PL-' . str_pad($next, 5, '0', STR_PAD_LEFT)]);
    }
}
