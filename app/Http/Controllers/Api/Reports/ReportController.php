<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Models\ReportDefinition;
use App\Services\ReportBuilder;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * GET /api/reports
     * List report definitions.
     */
    public function index(Request $request)
    {
        $query = ReportDefinition::where(function ($q) use ($request) {
            $q->where('company_id', $request->user()->company_id)
              ->orWhere('is_public', true)
              ->orWhere('is_template', true);
        })->orWhere('created_by', $request->user()->id);

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        $reports = $query->orderBy('sort_order')->get();
        return response()->json(['data' => $reports]);
    }

    /**
     * POST /api/reports
     * Create a new report definition.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'name_ar' => 'nullable|string',
            'category' => 'nullable|string',
            'base_table' => 'required|string',
            'selected_columns' => 'required|array',
            'filters' => 'nullable|array',
            'sort_by' => 'nullable|array',
            'group_by' => 'nullable|array',
            'aggregations' => 'nullable|array',
            'chart_config' => 'nullable|array',
            'is_public' => 'nullable|boolean',
        ]);

        $validated['code'] = \Str::slug($validated['name']);
        $validated['company_id'] = $request->user()->company_id;
        $validated['created_by'] = $request->user()->id;

        $report = ReportDefinition::create($validated);
        return response()->json(['data' => $report], 201);
    }

    /**
     * GET /api/reports/{id}
     */
    public function show(ReportDefinition $report)
    {
        $report->load('creator:id,name', 'sharedUsers:id,name');
        return response()->json(['data' => $report]);
    }

    /**
     * PUT /api/reports/{id}
     */
    public function update(Request $request, ReportDefinition $report)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'name_ar' => 'nullable|string',
            'category' => 'nullable|string',
            'selected_columns' => 'sometimes|array',
            'filters' => 'nullable|array',
            'sort_by' => 'nullable|array',
            'group_by' => 'nullable|array',
            'aggregations' => 'nullable|array',
            'chart_config' => 'nullable|array',
            'is_public' => 'nullable|boolean',
        ]);

        $report->update($validated);
        return response()->json(['data' => $report]);
    }

    /**
     * DELETE /api/reports/{id}
     */
    public function destroy(ReportDefinition $report)
    {
        $report->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * POST /api/reports/{id}/execute
     * Execute a report and return results.
     */
    public function execute(Request $request, ReportDefinition $report)
    {
        $companyId = $request->user()->company_id;
        $branchId = $request->input('branch_id')
            ?? $request->header('X-Branch-Id')
            ?? null;

        $results = ReportBuilder::execute($report, $companyId, $branchId);
        return response()->json(['data' => $results]);
    }

    /**
     * POST /api/reports/{id}/share
     * Share a report with users.
     */
    public function share(Request $request, ReportDefinition $report)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'permission' => 'nullable|string|in:view,edit',
        ]);

        foreach ($validated['user_ids'] as $userId) {
            $report->shares()->updateOrCreate(
                ['user_id' => $userId],
                ['permission' => $validated['permission'] ?? 'view']
            );
        }

        return response()->json(['message' => 'Report shared']);
    }

    /**
     * GET /api/reports/tables
     * Get available tables and their columns.
     */
    public function tables()
    {
        return response()->json(['data' => ReportBuilder::getAvailableTables()]);
    }

    /**
     * GET /api/reports/tables/{table}/schema
     * Get schema for a specific table.
     */
    public function tableSchema(string $table)
    {
        $schema = ReportBuilder::getTableSchema($table);
        return response()->json(['data' => $schema]);
    }

    /**
     * GET /api/reports/sales
     * Sales report summary.
     */
    public function sales(Request $request)
    {
        $companyId = $request->user()->company_id;

        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        $invoices = \App\Models\SalesInvoice::where('company_id', $companyId)
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->selectRaw('
                COUNT(*) as total_invoices,
                COALESCE(SUM(net_total), 0) as total_amount,
                COALESCE(SUM(paid_amount), 0) as total_paid,
                COALESCE(SUM(net_total - paid_amount), 0) as total_remaining
            ')
            ->first();

        return response()->json([
            'data' => [
                'period' => ['start_date' => $startDate, 'end_date' => $endDate],
                'summary' => $invoices,
            ],
        ]);
    }

    /**
     * GET /api/reports/reports/sales
     * Sales report summary (alias).
     */
    public function purchases(Request $request)
    {
        $companyId = $request->user()->company_id;

        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        $invoices = \App\Models\PurchaseInvoice::where('company_id', $companyId)
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->selectRaw('
                COUNT(*) as total_invoices,
                COALESCE(SUM(net_total), 0) as total_amount,
                COALESCE(SUM(paid_amount), 0) as total_paid,
                COALESCE(SUM(net_total - paid_amount), 0) as total_remaining
            ')
            ->first();

        return response()->json([
            'data' => [
                'period' => ['start_date' => $startDate, 'end_date' => $endDate],
                'summary' => $invoices,
            ],
        ]);
    }

    /**
     * GET /api/reports/inventory
     * Inventory report summary.
     */
    public function inventory(Request $request)
    {
        $companyId = $request->user()->company_id;

        $items = \App\Models\Item::where('company_id', $companyId)
            ->where('is_active', true)
            ->selectRaw('COUNT(*) as total_items')
            ->first();

        return response()->json(['data' => $items]);
    }

    /**
     * GET /api/reports/templates
     * Get all template reports.
     */
    public function templates()
    {
        $templates = ReportDefinition::where('is_template', true)
            ->orderBy('category')
            ->get();

        return response()->json(['data' => $templates]);
    }
}
