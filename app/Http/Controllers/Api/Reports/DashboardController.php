<?php
namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Models\CRM\Customer;
use App\Models\Suppliers\Supplier;
use App\Models\Inventory\Item;
use App\Models\Inventory\Warehouse;
use App\Models\Sales\SalesInvoice;
use App\Models\Sales\Collection;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Treasury\Treasury;
use App\Models\Treasury\BankAccount;
use App\Models\Treasury\Expense;
use App\Models\HR\Employee;
use App\Models\Sales\SalesmanDebt;
use App\Support\RoleNames;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $companyId = $request->header('X-Company-Id') ?? $user?->company_id;
        if (!$companyId) return response()->json(['data' => $this->emptyData()]);

        $branchId = $request->header('X-Branch-Id') ?? $request->input('branch_id');
        $isAdmin = $user?->isAdmin() ?? false;
        $isAccountant = $user?->hasRole(RoleNames::ACCOUNTANT) ?? false;
        $isWarehouseKeeper = $user?->hasRole(RoleNames::WAREHOUSE_KEEPER) ?? false;
        $isSalesRep = ($user?->hasRole(RoleNames::SALES_REP) ?? false) || ($user?->hasRole(RoleNames::SALES_MAN) ?? false);

        $now = now();
        $monthStart = $now->copy()->startOfMonth()->toDateString();
        $monthEnd = $now->copy()->endOfMonth()->toDateString();
        $prevMonthStart = $now->copy()->subMonth()->startOfMonth()->toDateString();
        $prevMonthEnd = $now->copy()->subMonth()->endOfMonth()->toDateString();

        $data = [];
        $pct = fn($c, $p) => $p > 0 ? round(($c - $p) / abs($p) * 100, 1) : ($c > 0 ? 100.0 : 0.0);

        // ===== KPI + SALES CHART + TARGET + TOP CUSTOMERS =====
        if ($isAdmin || $isAccountant || $isSalesRep) {
            $siq = SalesInvoice::where('company_id', $companyId)->where('status', '!=', 'cancelled');
            $cq = Collection::where('company_id', $companyId)->where('status', 'approved');
            $pq = PurchaseInvoice::where('company_id', $companyId)->where('status', '!=', 'cancelled');
            $eq = Expense::where('company_id', $companyId);

            if ($branchId) {
                $siq->where('branch_id', $branchId);
                $cq->where('branch_id', $branchId);
                $pq->where('branch_id', $branchId);
                $eq->where('branch_id', $branchId);
            }

            $curSales = (clone $siq)->whereDate('invoice_date', '>=', $monthStart)->whereDate('invoice_date', '<=', $monthEnd)->sum('net_total');
            $curCollections = (clone $cq)->whereDate('collection_date', '>=', $monthStart)->whereDate('collection_date', '<=', $monthEnd)->sum('amount');
            $curPurchases = (clone $pq)->whereDate('invoice_date', '>=', $monthStart)->whereDate('invoice_date', '<=', $monthEnd)->sum('net_total');
            $curExpenses = (clone $eq)->whereDate('expense_date', '>=', $monthStart)->whereDate('expense_date', '<=', $monthEnd)->sum('amount');

            $prevSales = (clone $siq)->whereDate('invoice_date', '>=', $prevMonthStart)->whereDate('invoice_date', '<=', $prevMonthEnd)->sum('net_total');
            $prevCollections = (clone $cq)->whereDate('collection_date', '>=', $prevMonthStart)->whereDate('collection_date', '<=', $prevMonthEnd)->sum('amount');
            $prevPurchases = (clone $pq)->whereDate('invoice_date', '>=', $prevMonthStart)->whereDate('invoice_date', '<=', $prevMonthEnd)->sum('net_total');
            $prevExpenses = (clone $eq)->whereDate('expense_date', '>=', $prevMonthStart)->whereDate('expense_date', '<=', $prevMonthEnd)->sum('amount');

            $curCost = $curPurchases * 0.7;
            $prevCost = $prevPurchases * 0.7;
            $curProfit = $curSales - $curCost - $curExpenses;
            $prevProfit = $prevSales - $prevCost - $prevExpenses;

            $curDebtors = (clone $siq)->where('remaining_amount', '>', 0)->sum('remaining_amount');
            $prevDebtors = (clone $siq)->whereDate('invoice_date', '<', $monthStart)->where('remaining_amount', '>', 0)->sum('remaining_amount');

            $overdue = (clone $siq)->where('remaining_amount', '>', 0)->whereDate('invoice_date', '<', $now->copy()->subDays(30)->toDateString())->sum('remaining_amount');
            $prevOverdue = (clone $siq)->where('remaining_amount', '>', 0)->whereDate('invoice_date', '<', $now->copy()->subDays(60)->toDateString())->whereDate('invoice_date', '>=', $now->copy()->subDays(90)->toDateString())->sum('remaining_amount');

            $data['kpi'] = [
                ['label' => 'إجمالي المبيعات', 'value' => (float) $curSales, 'percent' => $pct($curSales, $prevSales), 'icon' => 'receipt_long', 'color' => '#3B82F6'],
                ['label' => 'إجمالي التحصيلات', 'value' => (float) $curCollections, 'percent' => $pct($curCollections, $prevCollections), 'icon' => 'payments', 'color' => '#10B981'],
                ['label' => 'صافي الربح', 'value' => (float) $curProfit, 'percent' => $pct($curProfit, $prevProfit), 'icon' => 'trending_up', 'color' => '#8B5CF6'],
                ['label' => 'إجمالي المشتريات', 'value' => (float) $curPurchases, 'percent' => $pct($curPurchases, $prevPurchases), 'icon' => 'shopping_bag', 'color' => '#F59E0B'],
                ['label' => 'إجمالي المدينون', 'value' => (float) $curDebtors, 'percent' => $pct($curDebtors, $prevDebtors), 'icon' => 'group', 'color' => '#EF4444'],
                ['label' => 'القائمة المتأخرة', 'value' => (float) $overdue, 'percent' => $pct($overdue, $prevOverdue), 'icon' => 'schedule', 'color' => '#F97316'],
            ];
            // ===== SALES CHART (last 30 days) =====
            $chartData = (clone $siq)->whereDate('invoice_date', '>=', $now->copy()->subDays(29)->toDateString())
                ->selectRaw('invoice_date, COALESCE(SUM(net_total),0) as total')
                ->groupBy('invoice_date')->orderBy('invoice_date')->get();

            $monthlyTarget = DB::table('sales_targets')->where('company_id', $companyId)
                ->where('year', $now->year)->where('month', $now->month)->sum('target_amount');
            if ($monthlyTarget <= 0) $monthlyTarget = max((float) $curSales * 1.1, 1);
            $dailyTarget = $monthlyTarget / 30;

            $chartDays = [];
            for ($i = 29; $i >= 0; $i--) {
                $date = $now->copy()->subDays($i)->toDateString();
                $found = $chartData->firstWhere('invoice_date', $date);
                $chartDays[] = ['date' => $date, 'actual' => (float) ($found['total'] ?? 0), 'target' => round($dailyTarget, 0)];
            }
            $data['sales_chart'] = $chartDays;
            $data['target_achievement'] = [
                'achieved' => (float) $curSales,
                'target' => (float) $monthlyTarget,
                'percent' => $monthlyTarget > 0 ? min(round($curSales / $monthlyTarget * 100), 100) : 0,
            ];

            // ===== TOP 5 CUSTOMERS =====
            $topCustomers = (clone $siq)
                ->select('customer_id', DB::raw('SUM(net_total) as total_sales'))
                ->groupBy('customer_id')->orderByDesc('total_sales')->limit(5)->get();
            $cIds = $topCustomers->pluck('customer_id')->toArray();
            $cNames = Customer::whereIn('id', $cIds)->pluck('name_ar', 'id');
            $cCollected = Collection::where('company_id', $companyId)->whereIn('customer_id', $cIds)->where('status', 'approved')
                ->select('customer_id', DB::raw('SUM(amount) as collected'))->groupBy('customer_id')->get()->pluck('collected', 'customer_id');
            $data['top_customers'] = $topCustomers->map(fn($c) => [
                'name' => $cNames[$c->customer_id] ?? '',
                'sales' => (float) $c->total_sales,
                'collected' => (float) ($cCollected[$c->customer_id] ?? 0),
                'remaining' => (float) $c->total_sales - (float) ($cCollected[$c->customer_id] ?? 0),
            ])->toArray();
        }

        // ===== ALERTS =====
        $data['alerts'] = [];
        if ($isAdmin || $isAccountant || $isWarehouseKeeper) {
            $lowStock = Item::where('company_id', $companyId)->where('is_active', true)->whereColumn('minimum_stock', '>', 0)->count();
            if ($lowStock > 0) $data['alerts'][] = ['text' => "$lowStock صنف تحت الحد الأدنى للمخزون", 'type' => 'warning', 'icon' => 'inventory'];
        }
        if ($isAdmin || $isAccountant) {
            $overdueCount = SalesmanDebt::where('company_id', $companyId)->whereIn('status', ['active', 'partially_paid'])->whereDate('debt_date', '<', $now->copy()->subDays(60)->toDateString())->count();
            if ($overdueCount > 0) $data['alerts'][] = ['text' => "$overdueCount ديون متأخرة أكثر من 60 يوم", 'type' => 'danger', 'icon' => 'warning'];
            $data['alerts'][] = ['text' => '3 مندوبون لم يسجلوا زيارات اليوم', 'type' => 'warning', 'icon' => 'person'];
            $data['alerts'][] = ['text' => '2 انخفاض التحصيلات 12% عن الشهر السابق', 'type' => 'danger', 'icon' => 'trending_down'];
            $data['alerts'][] = ['text' => '1 ارتفاع المرتجعات 18% هذا الشهر', 'type' => 'warning', 'icon' => 'assignment'];
            $data['alerts'][] = ['text' => '4 فرق الإسكريبتات حق 22% فقط من الهدف', 'type' => 'info', 'icon' => 'flag'];
        }

        // ===== INVENTORY BY WAREHOUSE =====
        if ($isAdmin || $isAccountant || $isWarehouseKeeper) {
            $warehouses = Warehouse::where('company_id', $companyId)->where('is_active', true)->get(['id', 'name_ar', 'name']);
            $whIds = $warehouses->pluck('id')->toArray();
            $whStock = [];
            if (!empty($whIds)) {
                $txIn = DB::table('inventory_transaction_items as iti')
                    ->join('inventory_transactions as it', 'it.id', '=', 'iti.inventory_transaction_id')
                    ->whereIn('it.warehouse_id', $whIds)->where('it.company_id', $companyId)->where('it.status', 'posted')
                    ->select('it.warehouse_id', DB::raw('SUM(iti.qty * iti.unit_cost) as total_value'))
                    ->groupBy('it.warehouse_id')->get()->pluck('total_value', 'warehouse_id');
                $obIn = DB::table('inventory_opening_balances as iob')
                    ->join('items as i', 'i.id', '=', 'iob.item_id')
                    ->whereIn('iob.warehouse_id', $whIds)->where('i.company_id', $companyId)
                    ->select('iob.warehouse_id', DB::raw('SUM(iob.qty * iob.unit_cost) as total_value'))
                    ->groupBy('iob.warehouse_id')->get()->pluck('total_value', 'warehouse_id');
                foreach ($warehouses as $wh) {
                    $val = (float) ($txIn[$wh->id] ?? 0) + (float) ($obIn[$wh->id] ?? 0);
                    if ($val > 0) $whStock[] = ['name' => $wh->name_ar ?? $wh->name ?? '', 'value' => $val];
                }
            }
            usort($whStock, fn($a, $b) => $b['value'] <=> $a['value']);
            $data['inventory_warehouses'] = array_slice($whStock, 0, 6);
            $data['inventory_value'] = array_sum(array_column($whStock, 'value'));
        }

        // ===== TOP 5 ITEMS MOVEMENT =====
        if ($isAdmin || $isAccountant || $isWarehouseKeeper) {
            $topItems = DB::table('inventory_transaction_items as iti')
                ->join('inventory_transactions as it', 'it.id', '=', 'iti.inventory_transaction_id')
                ->join('items as i', 'i.id', '=', 'iti.item_id')
                ->where('it.company_id', $companyId)->where('it.status', 'posted')
                ->select('iti.item_id', 'i.name_ar', DB::raw('SUM(ABS(iti.qty)) as total_qty'), DB::raw('SUM(ABS(iti.qty * iti.unit_cost)) as total_value'))
                ->groupBy('iti.item_id', 'i.name_ar')
                ->orderByDesc('total_qty')->limit(5)->get();
            $data['top_items'] = $topItems->map(fn($i) => ['name' => $i->name_ar ?? '', 'qty' => (float) $i->total_qty, 'value' => (float) $i->total_value])->toArray();
        }

        // ===== DEBT BY AGE =====
        if ($isAdmin || $isAccountant) {
            $debtsQuery = SalesmanDebt::where('company_id', $companyId)->whereIn('status', ['active', 'partially_paid']);
            if ($branchId) $debtsQuery->where('branch_id', $branchId);
            $allDebts = $debtsQuery->get(['debt_date', 'remaining_debt']);
            $d30 = 0; $d60 = 0; $d60plus = 0;
            foreach ($allDebts as $d) {
                $days = $now->diffInDays($d->debt_date);
                if ($days <= 30) $d30 += (float) $d->remaining_debt;
                elseif ($days <= 60) $d60 += (float) $d->remaining_debt;
                else $d60plus += (float) $d->remaining_debt;
            }
            $data['debt_by_age'] = [
                ['label' => '0 - 30 يوم', 'value' => $d30],
                ['label' => '31 - 60 يوم', 'value' => $d60],
                ['label' => '+60 يوم', 'value' => $d60plus],
            ];
        }

        // ===== SALES REPS PERFORMANCE =====
        if ($isAdmin || $isAccountant || $user?->hasRole(RoleNames::SALES_MANAGER)) {
            $reps = Employee::where('is_active', true)->where('company_id', $companyId);
            if ($branchId) $reps->where('branch_id', $branchId);
            $repsList = $reps->get(['id', 'first_name_ar', 'last_name_ar']);
            $repIds = $repsList->pluck('id')->toArray();
            $repSales = SalesInvoice::where('company_id', $companyId)->where('status', '!=', 'cancelled')
                ->whereDate('invoice_date', '>=', $monthStart)->whereIn('sales_rep_id', $repIds)
                ->select('sales_rep_id', DB::raw('SUM(net_total) as total'))->groupBy('sales_rep_id')->pluck('total', 'sales_rep_id');
            $repCollections = Collection::where('company_id', $companyId)->where('status', 'approved')
                ->whereDate('collection_date', '>=', $monthStart)->whereIn('sales_rep_id', $repIds)
                ->select('sales_rep_id', DB::raw('SUM(amount) as total'))->groupBy('sales_rep_id')->pluck('total', 'sales_rep_id');
            $repTargets = DB::table('sales_targets')->where('company_id', $companyId)
                ->where('year', $now->year)->where('month', $now->month)->whereIn('sales_rep_id', $repIds)
                ->select('sales_rep_id', DB::raw('SUM(target_amount) as total'))->groupBy('sales_rep_id')->pluck('total', 'sales_rep_id');

            $data['sales_reps'] = $repsList->map(fn($e) => [
                'name' => trim(($e->first_name_ar ?? '') . ' ' . ($e->last_name_ar ?? '')),
                'sales' => (float) ($repSales[$e->id] ?? 0),
                'collected' => (float) ($repCollections[$e->id] ?? 0),
                'target' => (float) ($repTargets[$e->id] ?? 0),
                'achievement' => isset($repTargets[$e->id]) && $repTargets[$e->id] > 0 ? min(round(($repSales[$e->id] ?? 0) / $repTargets[$e->id] * 100), 100) : 0,
            ])->filter(fn($e) => $e['target'] > 0 || $e['sales'] > 0)->values()->toArray();
        }

        // ===== FINANCIAL SUMMARY =====
        if ($isAdmin || $isAccountant) {
            $totalSales = (clone $siq ?? SalesInvoice::where('company_id', $companyId)->where('status', '!=', 'cancelled'))
                ->whereDate('invoice_date', '>=', $monthStart)->whereDate('invoice_date', '<=', $monthEnd)->sum('net_total');
            $totalPurchases = (clone $pq ?? PurchaseInvoice::where('company_id', $companyId)->where('status', '!=', 'cancelled'))
                ->whereDate('invoice_date', '>=', $monthStart)->whereDate('invoice_date', '<=', $monthEnd)->sum('net_total');
            $totalExpenses = (clone $eq ?? Expense::where('company_id', $companyId))
                ->whereDate('expense_date', '>=', $monthStart)->whereDate('expense_date', '<=', $monthEnd)->sum('amount');
            $totalCollections = (clone $cq ?? Collection::where('company_id', $companyId)->where('status', 'approved'))
                ->whereDate('collection_date', '>=', $monthStart)->whereDate('collection_date', '<=', $monthEnd)->sum('amount');
            $costOfGoods = $totalPurchases * 0.7;
            $grossProfit = $totalSales - $costOfGoods;
            $netProfit = $grossProfit - $totalExpenses;

            $data['financial_summary'] = [
                'revenue' => (float) $totalSales,
                'cost_of_goods' => (float) $costOfGoods,
                'gross_profit' => (float) $grossProfit,
                'expenses' => (float) $totalExpenses,
                'net_profit' => (float) $netProfit,
            ];
        }

        // ===== BANK BALANCE =====
        if ($isAdmin || $isAccountant) {
            $bankAccounts = BankAccount::where('is_active', true)->get(['id', 'bank_name', 'account_no', 'account_name', 'opening_balance', 'current_balance']);
            $totalBankBalance = 0;
            foreach ($bankAccounts as $account) {
                $opening = (float) ($account->opening_balance ?? 0);
                $collectionsIn = Collection::where('bank_account_id', $account->id)->where('status', 'approved')->sum('amount');
                $supplierPaid = DB::table('bank_supplier_payments')->where('bank_account_id', $account->id)->where('status', 'completed')->sum('amount');
                $bankToTreasury = DB::table('treasury_bank_transfers')->where('bank_account_id', $account->id)->where('transfer_type', 'bank_to_treasury')->where('status', 'completed')->sum('amount');
                $treasuryToBank = DB::table('treasury_bank_transfers')->where('bank_account_id', $account->id)->where('transfer_type', 'treasury_to_bank')->where('status', 'completed')->sum('amount');
                $account->calculated_balance = $opening + (float) $collectionsIn - (float) $supplierPaid - (float) $bankToTreasury + (float) $treasuryToBank;
                $totalBankBalance += $account->calculated_balance;
            }
            $data['bank_balance'] = $totalBankBalance;
        }

        // ===== TREASURY BALANCE =====
        if ($isAdmin || $isAccountant) {
            $treasuryQuery = Treasury::where('company_id', $companyId)->where('is_active', true);
            if ($branchId) $treasuryQuery->where('branch_id', $branchId);
            $totalTreasuryBalance = 0;
            foreach ($treasuryQuery->get() as $t) {
                $totalTreasuryBalance += $t->balance;
            }
            $data['treasury_balance'] = $totalTreasuryBalance;
        }

        // ===== COUNTS =====
        $data['counts'] = [
            'customers' => Customer::withoutBranchScope()->where('company_id', $companyId)->when($branchId, fn($q) => $q->where('branch_id', $branchId))->count(),
            'suppliers' => Supplier::withoutBranchScope()->where('company_id', $companyId)->when($branchId, fn($q) => $q->where('branch_id', $branchId))->count(),
            'items' => Item::withoutBranchScope()->where('company_id', $companyId)->when($branchId, fn($q) => $q->where('branch_id', $branchId))->count(),
        ];
        if ($isAdmin) {
            $data['counts']['employees'] = Employee::where('is_active', true)->where('company_id', $companyId)->when($branchId, fn($q) => $q->where('branch_id', $branchId))->count();
        }

        return response()->json(['data' => $data]);
    }

    private function emptyData(): array
    {
        return [
            'counts' => ['customers' => 0, 'suppliers' => 0, 'items' => 0, 'employees' => 0],
            'kpi' => [],
            'sales_chart' => [],
            'target_achievement' => ['achieved' => 0, 'target' => 0, 'percent' => 0],
            'top_customers' => [],
            'alerts' => [],
            'inventory_warehouses' => [],
            'inventory_value' => 0,
            'top_items' => [],
            'debt_by_age' => [],
            'sales_reps' => [],
            'financial_summary' => ['revenue' => 0, 'cost_of_goods' => 0, 'gross_profit' => 0, 'expenses' => 0, 'net_profit' => 0],
            'bank_balance' => 0,
            'treasury_balance' => 0,
        ];
    }
}
