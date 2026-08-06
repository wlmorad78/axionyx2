<?php

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\MarketingSupportType;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class MarketingSupportTypeController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = MarketingSupportType::with($with);

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('code', 'like', "%$s%")
                    ->orWhere('name', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('marketing_support_type', 'store'));
        return response()->json(MarketingSupportType::create($data), 201);
    }

    public function show(MarketingSupportType $marketingSupportType)
    {
        return $marketingSupportType->load(['marketingSupports']);
    }

    public function update(Request $request, MarketingSupportType $marketingSupportType)
    {
        $data = $request->validate(ValidationRules::for('marketing_support_type', 'update', $marketingSupportType));
        $marketingSupportType->update($data);
        return response()->json($marketingSupportType);
    }

    public function destroy(MarketingSupportType $marketingSupportType)
    {
        $marketingSupportType->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = MarketingSupportType::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        MarketingSupportType::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('marketing_support_type', 'store');
    }
}
