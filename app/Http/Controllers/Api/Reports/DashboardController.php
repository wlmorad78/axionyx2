<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Models\CRM\Customer;
use App\Models\Suppliers\Supplier;
use App\Models\Inventory\Item;
use App\Models\Sales\SalesInvoice;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Treasury\PaymentVoucher;
use App\Models\Treasury\ReceiptVoucher;
use App\Models\Treasury\Treasury;
use App\Models\Sales\SalesmanDebt;
use App\Support\RoleNames;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $companyId = $request->header('X-Company-Id') ?? $user?->company_id;

        if (!$companyId) {
            return response()->json(['data' => $this->emptyData()]);
        }

        $branchId = $request->header('X-Branch-Id')
            ?? $request->input('branch_id')
            ?? null;

        $userRoles = $user?->roles->pluck('name')->toArray() ?? [];
        $isAdmin = in_array(RoleNames::ADMIN, $userRoles);
        $isAccountant = in_array(RoleNames::ACCOUNTANT, $userRoles);
        $isWarehouseKeeper = in_array(RoleNames::WAREHOUSE_KEEPER, $userRoles);
        $isSalesRep = in_array(RoleNames::SALES_REP, $userRoles) || in_array(RoleNames::SALES_MAN, $userRoles);

        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        $data = [];

        // Sales data - visible to Admin, Branch Manager, Accountant, Sales Rep
        if ($isAdmin || $isAccountant || $isSalesRep) {
            $todaySales = SalesInvoice::where('company_id', $companyId)
                ->where('invoice_date', $today)
                ->where('status', '!=', 'cancelled');

            $monthSales = SalesInvoice::where('company_id', $companyId)
                ->where('invoice_date', '>=', $monthStart)
                ->where('status', '!=', 'cancelled');

            $unpaidSales = SalesInvoice::where('company_id', $companyId)
                ->where('status', 'posted')
                ->where('remaining_amount', '>', 0);

            $recentSales = SalesInvoice::where('company_id', $companyId)
                ->with('customer:id,name_ar,name_en');

            $salesChart = SalesInvoice::where('company_id', $companyId)
                ->where('invoice_date', '>=', now()->subDays(6)->toDateString())
                ->where('status', '!=', 'cancelled');

            if ($branchId) {
                $todaySales = $todaySales->where('branch_id', $branchId);
                $monthSales = $monthSales->where('branch_id', $branchId);
                $unpaidSales = $unpaidSales->where('branch_id', $branchId);
                $recentSales = $recentSales->where('branch_id', $branchId);
                $salesChart = $salesChart->where('branch_id', $branchId);
            }

            $todaySales = $todaySales->selectRaw('COUNT(*) as count, COALESCE(SUM(net_total), 0) as total')->first();
            $monthSales = $monthSales->selectRaw('COUNT(*) as count, COALESCE(SUM(net_total), 0) as total')->first();
            $unpaidSales = $unpaidSales->sum('remaining_amount');
            $recentSales = $recentSales->latest('invoice_date')->limit(5)
                ->get(['id', 'invoice_no', 'invoice_date', 'net_total', 'status', 'customer_id']);
            $salesChart = $salesChart->selectRaw('invoice_date, COALESCE(SUM(net_total), 0) as total, COUNT(*) as count')
                ->groupBy('invoice_date')->orderBy('invoice_date')->get();

            $data['today_sales'] = ['count' => $todaySales->count, 'total' => (float) $todaySales->total];
            $data['month_sales'] = ['count' => $monthSales->count, 'total' => (float) $monthSales->total];
            $data['unpaid_sales'] = (float) $unpaidSales;
            $data['recent_sales'] = $recentSales;
            $data['sales_chart'] = $salesChart;
        }

        // Purchase data - visible to Admin, Accountant
        if ($isAdmin || $isAccountant) {
            $todayPurchases = PurchaseInvoice::where('company_id', $companyId)
                ->where('invoice_date', $today)
                ->where('status', '!=', 'cancelled');

            $monthPurchases = PurchaseInvoice::where('company_id', $companyId)
                ->where('invoice_date', '>=', $monthStart)
                ->where('status', '!=', 'cancelled');

            $unpaidPurchases = PurchaseInvoice::where('company_id', $companyId)
                ->where('status', 'posted')
                ->where('remaining_amount', '>', 0);

            $recentPurchases = PurchaseInvoice::where('company_id', $companyId)
                ->with('supplier:id,supplier_name');

            if ($branchId) {
                $todayPurchases = $todayPurchases->where('branch_id', $branchId);
                $monthPurchases = $monthPurchases->where('branch_id', $branchId);
                $unpaidPurchases = $unpaidPurchases->where('branch_id', $branchId);
                $recentPurchases = $recentPurchases->where('branch_id', $branchId);
            }

            $todayPurchases = $todayPurchases->selectRaw('COUNT(*) as count, COALESCE(SUM(net_total), 0) as total')->first();
            $monthPurchases = $monthPurchases->selectRaw('COUNT(*) as count, COALESCE(SUM(net_total), 0) as total')->first();
            $unpaidPurchases = $unpaidPurchases->sum('remaining_amount');
            $recentPurchases = $recentPurchases->latest('invoice_date')->limit(5)
                ->get(['id', 'invoice_no', 'invoice_date', 'net_total', 'status', 'supplier_id']);

            $data['today_purchases'] = ['count' => $todayPurchases->count, 'total' => (float) $todayPurchases->total];
            $data['month_purchases'] = ['count' => $monthPurchases->count, 'total' => (float) $monthPurchases->total];
            $data['unpaid_purchases'] = (float) $unpaidPurchases;
            $data['recent_purchases'] = $recentPurchases;
        }

        // Treasury data - visible to Admin, Accountant
        if ($isAdmin || $isAccountant) {
            $treasuryQuery = Treasury::where('company_id', $companyId)
                ->where('is_active', true);

            if ($branchId) {
                $treasuryQuery = $treasuryQuery->where('branch_id', $branchId);
            }

            $data['treasury_balance'] = (float) $treasuryQuery->sum('balance');
        }

        // Counts - visible to all roles
        $totalCustomers = Customer::withoutBranchScope()->where('company_id', $companyId);
        $totalSuppliers = Supplier::withoutBranchScope()->where('company_id', $companyId);
        $totalItems = Item::withoutBranchScope()->where('company_id', $companyId);

        if ($branchId) {
            $totalCustomers = $totalCustomers->where('branch_id', $branchId);
            $totalSuppliers = $totalSuppliers->where('branch_id', $branchId);
            $totalItems = $totalItems->where('branch_id', $branchId);
        }

        $data['counts'] = [
            'customers' => $totalCustomers->count(),
            'suppliers' => $totalSuppliers->count(),
            'items' => $totalItems->count(),
        ];

        // Salesman debts - visible to Admin, Sales Manager
        if ($isAdmin || in_array(RoleNames::SALES_MANAGER, $userRoles)) {
            $activeDebts = SalesmanDebt::where('company_id', $companyId)
                ->whereIn('status', ['active', 'partially_paid']);

            $recentDebts = SalesmanDebt::where('company_id', $companyId)
                ->with('salesman:id,first_name,last_name');

            if ($branchId) {
                $activeDebts = $activeDebts->where('branch_id', $branchId);
                $recentDebts = $recentDebts->where('branch_id', $branchId);
            }

            $activeDebts = $activeDebts->selectRaw('COUNT(*) as count, COALESCE(SUM(remaining_debt), 0) as total')->first();
            $recentDebts = $recentDebts->latest('debt_date')->limit(5)
                ->get(['id', 'debt_no', 'debt_date', 'gross_debt', 'remaining_debt', 'status', 'salesman_id', 'notes']);

            $data['salesman_debts'] = [
                'active_count' => (int) $activeDebts->count,
                'active_total' => (float) $activeDebts->total,
                'recent' => $recentDebts,
            ];
        }

        return response()->json(['data' => $data]);
    }

    private function emptyData(): array
    {
        return [
            'counts' => ['customers' => 0, 'suppliers' => 0, 'items' => 0],
            'today_sales' => ['count' => 0, 'total' => 0],
            'today_purchases' => ['count' => 0, 'total' => 0],
            'month_sales' => ['count' => 0, 'total' => 0],
            'month_purchases' => ['count' => 0, 'total' => 0],
            'treasury_balance' => 0,
            'unpaid_sales' => 0,
            'unpaid_purchases' => 0,
            'salesman_debts' => ['active_count' => 0, 'active_total' => 0, 'recent' => []],
            'recent_sales' => [],
            'recent_purchases' => [],
            'sales_chart' => [],
        ];
    }
}
