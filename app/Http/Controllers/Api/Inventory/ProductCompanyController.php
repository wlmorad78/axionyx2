<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\ProductCompany;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ProductCompanyController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];

        \Log::info('ProductCompanyController@index', [
            'all_params' => $request->all(),
            'trashed' => $request->input('trashed'),
            'trashed_bool' => (bool) $request->input('trashed'),
            'company_id' => $request->input('company_id'),
        ]);

        $query = ProductCompany::with($with);

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

        if ($request->input('trashed')) {
            $query->onlyTrashed();
        }

        $result = $query->paginate($request->per_page ?? 15);

        \Log::info('ProductCompanyController@index result', [
            'count' => $result->count(),
            'total' => $result->total(),
            'items_ids' => $result->pluck('id')->toArray(),
        ]);

        return $result;
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('product_company', 'store'));

        return response()->json(ProductCompany::create($data), 201);
    }

    public function show($id)
    {
        $model = ProductCompany::withoutTrashed()->findOrFail($id);
        return response()->json($model->load(['company']));
    }

    public function update(Request $request, $id)
    {
        $model = ProductCompany::withoutTrashed()->findOrFail($id);
        $data = $request->validate(ValidationRules::for('product_company', 'update', $model));

        $model->update($data);
        $model->refresh();

        return response()->json($model);
    }

    public function destroy($id)
    {
        $model = ProductCompany::withoutTrashed()->findOrFail($id);
        $model->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = ProductCompany::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        ProductCompany::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function nextCode()
    {
        $last = ProductCompany::where('code', 'like', 'MFG-%')
            ->orderByDesc('code')
            ->first();

        $next = ($last && preg_match('/MFG-(\d+)-\d+/', $last->code, $m)) ? intval($m[1]) + 1 : 1;

        return response()->json(['code' => 'MFG-' . str_pad($next, 3, '0', STR_PAD_LEFT) . '-01']);
    }

    public function schema()
    {
        return ValidationRules::for('product_company', 'store');
    }
}
