<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\TradeProgramType;
use Illuminate\Http\Request;

class TradeProgramTypeController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = TradeProgramType::with($with);

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
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'code' => 'required|string|max:50|unique:trade_program_types,code,null,id,deleted_at,NULL',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);
        return response()->json(TradeProgramType::create($data), 201);
    }

    public function show(TradeProgramType $tradeProgramType)
    {
        return $tradeProgramType->load(['company']);
    }

    public function update(Request $request, TradeProgramType $tradeProgramType)
    {
        $data = $request->validate([
            'code' => 'sometimes|required|string|max:50|unique:trade_program_types,code,' . $tradeProgramType->id . ',deleted_at,NULL',
            'name_ar' => 'sometimes|required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);
        $tradeProgramType->update($data);
        return response()->json($tradeProgramType);
    }

    public function destroy(TradeProgramType $tradeProgramType)
    {
        $tradeProgramType->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = TradeProgramType::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        TradeProgramType::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function nextCode(Request $request)
    {
        $companyId = $request->company_id;
        $query = TradeProgramType::query()->withTrashed();
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        $last = $query->where('code', 'like', 'TP-%')->orderByRaw("CAST(SUBSTRING(code, 4) AS UNSIGNED) DESC")->first();
        if ($last && preg_match('/^TP-(\d+)$/', $last->code, $m)) {
            $next = (int) $m[1] + 1;
        } else {
            $next = 1;
        }
        return response()->json(['next_code' => 'TP-' . str_pad((string) $next, 3, '0', STR_PAD_LEFT)]);
    }
}
