<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Item;
use App\Models\SalesInvoice;
use App\Models\Collection;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardV2Controller extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $companyId = $request->header('X-Company-Id') ?? $user?->company_id;

            if (!$companyId) {
                return response()->json([
                    'success' => true,
                    'data' => $this->emptyData(),
                ]);
            }

            $now = now();
            $today = $now->toDateString();
            $monthStart = $now->copy()->startOfMonth()->toDateString();
            $monthEnd = $now->copy()->endOfMonth()->toDateString();

            $summary = $this->getSummary($companyId, $today, $monthStart, $monthEnd);
            $salesTrend = $this->getSalesTrend($companyId, $now);
            $topCustomers = $this->getTopCustomers($companyId, $monthStart, $monthEnd);
            $recentActivities = $this->getRecentActivities($companyId);
            $lowStockItems = $this->getLowStockItems($companyId);
            $alerts = $this->getAlerts($companyId, $summary, $lowStockItems);

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => $summary,
                    'sales_trend' => $salesTrend,
                    'top_customers' => $topCustomers,
                    'recent_activities' => $recentActivities,
                    'low_stock_items' => $lowStockItems,
                    'alerts' => $alerts,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function getSummary(int $companyId, string $today, string $monthStart, string $monthEnd): array
    {
        $todaySales = SalesInvoice::where('company_id', $companyId)
            ->whereDate('invoice_date', $today)
            ->where('status', 'posted')
            ->sum('total_amount');

        $todayCollections = Collection::where('company_id', $companyId)
            ->whereDate('collection_date', $today)
            ->where('status', 'approved')
            ->sum('amount');

        $todayExpenses = Expense::where('company_id', $companyId)
            ->whereDate('expense_date', $today)
            ->sum('amount');

        $monthSales = SalesInvoice::where('company_id', $companyId)
            ->whereDate('invoice_date', '>=', $monthStart)
            ->whereDate('invoice_date', '<=', $monthEnd)
            ->where('status', 'posted')
            ->sum('total_amount');

        $monthCogs = DB::table('sales_invoice_items as sii')
            ->join('sales_invoices as si', 'si.id', '=', 'sii.sales_invoice_id')
            ->where('si.company_id', $companyId)
            ->whereDate('si.invoice_date', '>=', $monthStart)
            ->whereDate('si.invoice_date', '<=', $monthEnd)
            ->where('si.status', 'posted')
            ->sum('sii.total_cost');

        $monthExpenses = Expense::where('company_id', $companyId)
            ->whereDate('expense_date', '>=', $monthStart)
            ->whereDate('expense_date', '<=', $monthEnd)
            ->sum('amount');

        $monthProfit = $monthSales - $monthCogs - $monthExpenses;

        $customersCount = Customer::where('company_id', $companyId)
            ->where('is_active', true)
            ->count();

        $pendingInvoices = SalesInvoice::where('company_id', $companyId)
            ->where('status', '!=', 'posted')
            ->count();

        $lowStockCount = $this->countLowStockItems($companyId);

        return [
            'today_sales' => (float) $todaySales,
            'today_collections' => (float) $todayCollections,
            'today_expenses' => (float) $todayExpenses,
            'month_sales' => (float) $monthSales,
            'month_profit' => (float) $monthProfit,
            'customers_count' => (int) $customersCount,
            'pending_invoices' => (int) $pendingInvoices,
            'low_stock_count' => (int) $lowStockCount,
        ];
    }

    private function getSalesTrend(int $companyId, $now): array
    {
        $days = collect();
        for ($i = 6; $i >= 0; $i--) {
            $days->push($now->copy()->subDays($i)->toDateString());
        }

        $salesByDate = SalesInvoice::where('company_id', $companyId)
            ->whereDate('invoice_date', '>=', $days->first())
            ->whereDate('invoice_date', '<=', $days->last())
            ->where('status', 'posted')
            ->selectRaw('DATE(invoice_date) as date, SUM(total_amount) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $collectionsByDate = Collection::where('company_id', $companyId)
            ->whereDate('collection_date', '>=', $days->first())
            ->whereDate('collection_date', '<=', $days->last())
            ->where('status', 'approved')
            ->selectRaw('DATE(collection_date) as date, SUM(amount) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        return $days->map(fn($date) => [
            'date' => $date,
            'sales' => (float) ($salesByDate[$date] ?? 0),
            'collections' => (float) ($collectionsByDate[$date] ?? 0),
        ])->values()->toArray();
    }

    private function getTopCustomers(int $companyId, string $monthStart, string $monthEnd): array
    {
        $topSales = SalesInvoice::where('company_id', $companyId)
            ->whereDate('invoice_date', '>=', $monthStart)
            ->whereDate('invoice_date', '<=', $monthEnd)
            ->where('status', 'posted')
            ->select('customer_id', DB::raw('SUM(total_amount) as total'), DB::raw('COUNT(*) as invoices'))
            ->groupBy('customer_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        if ($topSales->isEmpty()) {
            return [];
        }

        $customerIds = $topSales->pluck('customer_id')->toArray();
        $customers = Customer::whereIn('id', $customerIds)
            ->pluck('name_ar', 'id');

        return $topSales->map(fn($row) => [
            'id' => (int) $row->customer_id,
            'name' => $customers[$row->customer_id] ?? '',
            'total' => (float) $row->total,
            'invoices' => (int) $row->invoices,
        ])->toArray();
    }

    private function getRecentActivities(int $companyId): array
    {
        $recentSales = SalesInvoice::where('company_id', $companyId)
            ->latest('invoice_date')
            ->limit(5)
            ->get(['id', 'invoice_no', 'invoice_date', 'total_amount', 'status'])
            ->map(fn($inv) => [
                'type' => 'sale',
                'description' => 'Invoice ' . $inv->invoice_no,
                'time' => $inv->invoice_date ? $inv->invoice_date->format('h:i A') : '',
                'amount' => (float) $inv->total_amount,
            ]);

        $recentCollections = Collection::where('company_id', $companyId)
            ->latest('collection_date')
            ->limit(5)
            ->get(['id', 'collection_no', 'collection_date', 'amount', 'status'])
            ->map(fn($col) => [
                'type' => 'collection',
                'description' => 'Collection ' . ($col->collection_no ?? '#' . $col->id),
                'time' => $col->collection_date ? $col->collection_date->format('h:i A') : '',
                'amount' => (float) $col->amount,
            ]);

        return $recentSales->concat($recentCollections)
            ->sortByDesc('time')
            ->take(10)
            ->values()
            ->toArray();
    }

    private function getLowStockItems(int $companyId): array
    {
        $items = Item::where('company_id', $companyId)
            ->where('is_active', true)
            ->get(['id', 'name_ar', 'name']);

        $lowStockItems = [];

        foreach ($items as $item) {
            $stock = DB::table('inventory_transaction_items as iti')
                ->join('inventory_transactions as it', 'it.id', '=', 'iti.inventory_transaction_id')
                ->where('iti.item_id', $item->id)
                ->where('it.company_id', $companyId)
                ->where('it.status', 'posted')
                ->sum('iti.qty');

            if ($stock < 5) {
                $unit = DB::table('item_units')
                    ->where('item_id', $item->id)
                    ->where('is_default', true)
                    ->first();

                $lowStockItems[] = [
                    'id' => (int) $item->id,
                    'name' => $item->name_ar ?? $item->name ?? '',
                    'current_stock' => (float) $stock,
                    'unit' => $unit ? ($unit->name ?? '') : '',
                ];
            }
        }

        return $lowStockItems;
    }

    private function countLowStockItems(int $companyId): int
    {
        $items = Item::where('company_id', $companyId)
            ->where('is_active', true)
            ->pluck('id');

        $count = 0;
        foreach ($items as $itemId) {
            $stock = DB::table('inventory_transaction_items as iti')
                ->join('inventory_transactions as it', 'it.id', '=', 'iti.inventory_transaction_id')
                ->where('iti.item_id', $itemId)
                ->where('it.company_id', $companyId)
                ->where('it.status', 'posted')
                ->sum('iti.qty');

            if ($stock < 5) {
                $count++;
            }
        }

        return $count;
    }

    private function getAlerts(int $companyId, array $summary, array $lowStockItems): array
    {
        $alerts = [];

        if (!empty($lowStockItems)) {
            $alerts[] = [
                'type' => 'warning',
                'text' => count($lowStockItems) . ' items below minimum stock level',
            ];
        }

        if ($summary['pending_invoices'] > 0) {
            $alerts[] = [
                'type' => 'info',
                'text' => $summary['pending_invoices'] . ' invoices pending posting',
            ];
        }

        if ($summary['today_sales'] == 0 && now()->hour >= 12) {
            $alerts[] = [
                'type' => 'warning',
                'text' => 'No sales recorded today yet',
            ];
        }

        if ($summary['today_collections'] == 0 && now()->hour >= 12) {
            $alerts[] = [
                'type' => 'warning',
                'text' => 'No collections recorded today yet',
            ];
        }

        return $alerts;
    }

    private function emptyData(): array
    {
        return [
            'summary' => [
                'today_sales' => 0,
                'today_collections' => 0,
                'today_expenses' => 0,
                'month_sales' => 0,
                'month_profit' => 0,
                'customers_count' => 0,
                'pending_invoices' => 0,
                'low_stock_count' => 0,
            ],
            'sales_trend' => [],
            'top_customers' => [],
            'recent_activities' => [],
            'low_stock_items' => [],
            'alerts' => [],
        ];
    }
}
