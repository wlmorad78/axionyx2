<?php

namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\MerchandisingAudit;
use Illuminate\Http\Request;

class MerchandisingAuditController extends Controller
{
    public function index(Request $request)
    {
        $query = MerchandisingAudit::with(['customer', 'salesRep', 'details', 'shelfAudits', 'refrigeratorAudits', 'photos']);

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('sales_rep_id')) {
            $query->where('sales_rep_id', $request->sales_rep_id);
        }

        if ($request->filled('audit_date_from') && $request->filled('audit_date_to')) {
            $query->whereBetween('audit_date', [$request->audit_date_from, $request->audit_date_to]);
        } elseif ($request->filled('audit_date_from')) {
            $query->where('audit_date', '>=', $request->audit_date_from);
        } elseif ($request->filled('audit_date_to')) {
            $query->where('audit_date', '<=', $request->audit_date_to);
        }

        $audits = $query->paginate($request->get('per_page', 15));

        return response()->json($audits);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required',
            'customer_id' => 'required',
            'sales_rep_id' => 'nullable',
            'visit_id' => 'nullable',
            'audit_date' => 'required|date',
            'audit_time' => 'nullable',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'overall_score' => 'nullable|numeric',
            'notes' => 'nullable',
        ]);

        $audit = MerchandisingAudit::create($validated);

        return response()->json($audit->load(['customer', 'salesRep', 'details', 'shelfAudits', 'refrigeratorAudits', 'photos']), 201);
    }

    public function show($id)
    {
        $audit = MerchandisingAudit::with(['customer', 'salesRep', 'details', 'shelfAudits', 'refrigeratorAudits', 'photos'])->findOrFail($id);

        return response()->json($audit);
    }

    public function update(Request $request, $id)
    {
        $audit = MerchandisingAudit::findOrFail($id);

        $validated = $request->validate([
            'company_id' => 'required',
            'customer_id' => 'required',
            'sales_rep_id' => 'nullable',
            'visit_id' => 'nullable',
            'audit_date' => 'required|date',
            'audit_time' => 'nullable',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'overall_score' => 'nullable|numeric',
            'notes' => 'nullable',
        ]);

        $audit->update($validated);

        return response()->json($audit->load(['customer', 'salesRep', 'details', 'shelfAudits', 'refrigeratorAudits', 'photos']));
    }

    public function destroy($id)
    {
        $audit = MerchandisingAudit::findOrFail($id);
        $audit->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    public function restore($id)
    {
        $audit = MerchandisingAudit::onlyTrashed()->findOrFail($id);
        $audit->restore();

        return response()->json($audit);
    }

    public function forceDelete($id)
    {
        $audit = MerchandisingAudit::onlyTrashed()->findOrFail($id);
        $audit->forceDelete();

        return response()->json(['message' => 'Permanently deleted']);
    }
}
