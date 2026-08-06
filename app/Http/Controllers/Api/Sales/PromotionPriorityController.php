<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{PromotionPriority};
use App\Support\ValidationRules;

class PromotionPriorityController extends Controller
{
    public function index(Request $request)
    {
        $query = PromotionPriority::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('priority', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('promotion_priority', 'create'));
        $promotionPriority = PromotionPriority::create($data);
        return response()->json($promotionPriority, 201);
    }

    public function show($id)
    {
        return PromotionPriority::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $promotionPriority = PromotionPriority::findOrFail($id);
        $data = $request->validate(ValidationRules::for('promotion_priority', 'update', $promotionPriority));
        $promotionPriority->update($data);
        return $promotionPriority;
    }

    public function destroy($id)
    {
        $promotionPriority = PromotionPriority::findOrFail($id);
        $promotionPriority->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $promotionPriority = PromotionPriority::withTrashed()->findOrFail($id);
        $promotionPriority->restore();
        return $promotionPriority;
    }

    public function forceDelete($id)
    {
        $promotionPriority = PromotionPriority::withTrashed()->findOrFail($id);
        $promotionPriority->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
