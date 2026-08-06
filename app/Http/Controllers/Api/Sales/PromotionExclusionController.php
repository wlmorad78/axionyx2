<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{PromotionExclusion};
use App\Support\ValidationRules;

class PromotionExclusionController extends Controller
{
    public function index(Request $request)
    {
        $query = PromotionExclusion::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('id', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('promotion_exclusion', 'create'));
        $promotionExclusion = PromotionExclusion::create($data);
        return response()->json($promotionExclusion, 201);
    }

    public function show($id)
    {
        return PromotionExclusion::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $promotionExclusion = PromotionExclusion::findOrFail($id);
        $data = $request->validate(ValidationRules::for('promotion_exclusion', 'update', $promotionExclusion));
        $promotionExclusion->update($data);
        return $promotionExclusion;
    }

    public function destroy($id)
    {
        $promotionExclusion = PromotionExclusion::findOrFail($id);
        $promotionExclusion->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $promotionExclusion = PromotionExclusion::withTrashed()->findOrFail($id);
        $promotionExclusion->restore();
        return $promotionExclusion;
    }

    public function forceDelete($id)
    {
        $promotionExclusion = PromotionExclusion::withTrashed()->findOrFail($id);
        $promotionExclusion->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
