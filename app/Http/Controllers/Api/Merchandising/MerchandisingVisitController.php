<?php

namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\MerchandisingVisit;
use Illuminate\Http\Request;

class MerchandisingVisitController extends Controller
{
    public function index(Request $request)
    {
        $query = MerchandisingVisit::with(['salesRep', 'customer']);

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('visit_date', 'like', "%{$s}%")
                    ->orWhere('overall_score', 'like', "%{$s}%");
            });
        }

        if ($request->filled('customer_id')) $query->where('customer_id', $request->customer_id);
        if ($request->filled('sales_rep_id')) $query->where('sales_rep_id', $request->sales_rep_id);
        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'sales_rep_id' => 'required|exists:employees,id',
            'customer_id' => 'required|exists:customers,id',
            'visit_date' => 'required|date',
            'visit_time' => 'nullable',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'overall_score' => 'numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $visit = MerchandisingVisit::create($data);
        return response()->json($visit, 201);
    }

    public function show($id)
    {
        return MerchandisingVisit::with(['salesRep', 'customer', 'details.checklist', 'photos'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $visit = MerchandisingVisit::findOrFail($id);

        $data = $request->validate([
            'company_id' => 'sometimes|required|exists:companies,id',
            'sales_rep_id' => 'sometimes|required|exists:employees,id',
            'customer_id' => 'sometimes|required|exists:customers,id',
            'visit_date' => 'sometimes|required|date',
            'visit_time' => 'nullable',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'overall_score' => 'numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $visit->update($data);
        return $visit;
    }

    public function destroy($id)
    {
        $visit = MerchandisingVisit::findOrFail($id);
        $visit->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $visit = MerchandisingVisit::withTrashed()->findOrFail($id);
        $visit->restore();
        return $visit;
    }

    public function forceDelete($id)
    {
        $visit = MerchandisingVisit::withTrashed()->findOrFail($id);
        $visit->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
