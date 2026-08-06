<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\DailyDistributionDashboard;
use Illuminate\Http\Request;

class DailyDistributionDashboardController extends Controller
{
    public function index(Request $request)
    {
        $query = DailyDistributionDashboard::with(['company', 'branch', 'salesRep', 'route']);

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('dashboard_date')) {
            $query->where('dashboard_date', $request->dashboard_date);
        }
        if ($request->filled('sales_rep_id')) {
            $query->where('sales_rep_id', $request->sales_rep_id);
        }
        if ($request->filled('route_id')) {
            $query->where('route_id', $request->route_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%");
            });
        }

        $dashboards = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($dashboards);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            \App\Support\ValidationRules::rules('daily_distribution_dashboard', false)
        );

        $dashboard = DailyDistributionDashboard::create($validated);

        return response()->json($dashboard->load(['company', 'branch', 'salesRep', 'route']), 201);
    }

    public function show($id)
    {
        $dashboard = DailyDistributionDashboard::with(['company', 'branch', 'salesRep', 'route'])->findOrFail($id);

        return response()->json($dashboard);
    }

    public function update(Request $request, $id)
    {
        $dashboard = DailyDistributionDashboard::findOrFail($id);

        $validated = $request->validate(
            \App\Support\ValidationRules::rules('daily_distribution_dashboard', true)
        );

        $dashboard->update($validated);

        return response()->json($dashboard->load(['company', 'branch', 'salesRep', 'route']));
    }

    public function destroy($id)
    {
        $dashboard = DailyDistributionDashboard::findOrFail($id);
        $dashboard->delete();

        return response()->json(['message' => 'Dashboard deleted successfully']);
    }

    public function restore($id)
    {
        $dashboard = DailyDistributionDashboard::withTrashed()->findOrFail($id);
        $dashboard->restore();

        return response()->json(['message' => 'Dashboard restored successfully']);
    }

    public function forceDelete($id)
    {
        $dashboard = DailyDistributionDashboard::withTrashed()->findOrFail($id);
        $dashboard->forceDelete();

        return response()->json(['message' => 'Dashboard permanently deleted']);
    }

    public function schema()
    {
        return response()->json([
            'columns' => [
                'id' => 'integer',
                'company_id' => 'integer',
                'branch_id' => 'integer',
                'dashboard_date' => 'date',
                'sales_rep_id' => 'integer',
                'route_id' => 'integer',
                'planned_customers' => 'integer',
                'visited_customers' => 'integer',
                'invoices_count' => 'integer',
                'sales_amount' => 'decimal',
                'returns_amount' => 'decimal',
                'collections_amount' => 'decimal',
                'loaded_amount' => 'decimal',
                'settled_amount' => 'decimal',
                'cash_difference' => 'decimal',
                'created_at' => 'timestamp',
                'updated_at' => 'timestamp',
                'deleted_at' => 'timestamp',
            ],
        ]);
    }
}
