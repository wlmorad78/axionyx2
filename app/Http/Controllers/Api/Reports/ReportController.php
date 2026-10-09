<?php
/**
 * =====================================================================
 * متحكم (Controller): ReportController
 * الوحدة (Module): التقارير واللوحات (Reports)
 * المورد (Resource): Report
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Report" ضمن وحدة "التقارير واللوحات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Models\ReportDefinition;
use App\Services\ReportBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

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
     * GET /api/reports/profit
     * Profit summary based on posted sales, item purchase prices, and expenses.
     */
    public function profit(Request $request)
    {
        $companyId = $request->user()->company_id;
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        $sales = DB::table('sales_invoices as si')
            ->where('si.company_id', $companyId)
            ->whereBetween('si.invoice_date', [$startDate, $endDate])
            ->where('si.status', '!=', 'cancelled')
            ->sum('si.net_total');

        $cost = DB::table('sales_invoice_items as sii')
            ->join('sales_invoices as si', 'si.id', '=', 'sii.sales_invoice_id')
            ->where('si.company_id', $companyId)
            ->whereBetween('si.invoice_date', [$startDate, $endDate])
            ->where('si.status', '!=', 'cancelled')
            ->sum('sii.total_cost');

        $itemProfit = DB::table('sales_invoice_items as sii')
            ->join('sales_invoices as si', 'si.id', '=', 'sii.sales_invoice_id')
            ->where('si.company_id', $companyId)
            ->whereBetween('si.invoice_date', [$startDate, $endDate])
            ->where('si.status', '!=', 'cancelled')
            ->sum('sii.profit');

        $expenses = \App\Models\Expense::where('company_id', $companyId)
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->sum('amount');

        $profit = (float) $itemProfit - (float) $expenses;

        return response()->json([
            'data' => [
                'summary' => [
                    'sales' => (float) $sales,
                    'cost' => (float) $cost,
                    'expenses' => (float) $expenses,
                    'profit' => $profit,
                    'margin' => $sales > 0 ? ($profit / $sales) * 100 : 0,
                ],
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

    public function customerDailySales(Request $request)
    {
        $request->validate([
            'date'              => 'nullable|date',
            'date_from'         => 'nullable|date',
            'date_to'           => 'nullable|date|after_or_equal:date_from',
            'territory_id'      => 'nullable|integer',
            'route_id'          => 'nullable|integer',
            'sales_rep_id'      => 'nullable|integer',
        ]);

        $companyId = $request->user()->company_id;
        $dateFrom = $request->input('date_from') ?? $request->input('date');
        $dateTo = $request->input('date_to') ?? $request->input('date');
        $territoryId = $request->input('territory_id');
        $routeId = $request->input('route_id');
        $salesRepId = $request->input('sales_rep_id');

        $salesQuery = DB::table('sales_invoices')
            ->where('sales_invoices.company_id', $companyId)
            ->whereDate('sales_invoices.invoice_date', '>=', $dateFrom)
            ->whereDate('sales_invoices.invoice_date', '<=', $dateTo)
            ->where('sales_invoices.status', 'posted')
            ->whereNull('sales_invoices.deleted_at');

        if ($salesRepId) {
            $salesQuery->where('sales_invoices.sales_rep_id', $salesRepId);
        }

        $salesRows = $salesQuery
            ->select(
                'customer_id',
                DB::raw('SUM(net_total) as total_sales'),
                DB::raw('SUM(paid_amount) as total_cash')
            )
            ->groupBy('customer_id')
            ->get()
            ->keyBy('customer_id');

        $customerIds = $salesRows->keys();

        if ($customerIds->isEmpty()) {
            return response()->json(['data' => ['customers' => []]]);
        }

        // تجميع الفواتير لكل (عميل + يوم): القيمة، الخصم، الصافي
        $discountDayExpr = 'COALESCE(item_discount_total, 0) + COALESCE(invoice_discount_total, 0)';
        $dayTotals = DB::table('sales_invoices')
            ->where('company_id', $companyId)
            ->whereDate('invoice_date', '>=', $dateFrom)
            ->whereDate('invoice_date', '<=', $dateTo)
            ->where('status', 'posted')
            ->whereNull('deleted_at')
            ->whereIn('customer_id', $customerIds)
            ->when($salesRepId, fn ($q) => $q->where('sales_rep_id', $salesRepId))
            ->groupBy('customer_id', DB::raw('DATE(invoice_date)'))
            ->select(
                'customer_id',
                DB::raw('DATE(invoice_date) as day'),
                DB::raw('COUNT(*) as invoices'),
                DB::raw('SUM(subtotal + tax_total) as invoice_total'),
                DB::raw("SUM($discountDayExpr) as discount"),
                DB::raw('SUM(net_total) as net_total')
            )
            ->get()
            ->groupBy(fn ($r) => $r->customer_id . '|' . $r->day);

        // Get latest sales_rep_id per customer (replaces correlated subquery)
        $repMap = DB::table('sales_invoices')
            ->where('company_id', $companyId)
            ->whereDate('invoice_date', '>=', $dateFrom)
            ->whereDate('invoice_date', '<=', $dateTo)
            ->where('status', 'posted')
            ->whereNull('deleted_at')
            ->whereIn('customer_id', $customerIds)
            ->select('customer_id', 'sales_rep_id')
            ->orderByDesc('id')
            ->get()
            ->unique('customer_id')
            ->mapWithKeys(fn($r) => [$r->customer_id => $r->sales_rep_id]);

        $repUserIds = $repMap->values()->unique()->values();

        $employees = $repUserIds->isEmpty() ? collect()->keyBy('user_id') : DB::table('employees')
            ->whereNull('deleted_at')
            ->whereIn('user_id', $repUserIds)
            ->select(
                'user_id',
                'id as emp_id',
                DB::raw("TRIM(COALESCE(first_name_ar, '') || ' ' || COALESCE(second_name_ar, '') || ' ' || COALESCE(third_name_ar, '') || ' ' || COALESCE(last_name_ar, '')) as sales_rep_name")
            )
            ->get()
            ->keyBy('user_id');

        $customers = DB::table('customers')
            ->whereNull('customers.deleted_at')
            ->whereIn('customers.id', $customerIds)
            ->select(
                'customers.id as customer_id',
                'customers.code as customer_code',
                'customers.name_ar as customer_name'
            )
            ->get()
            ->map(function ($c) use ($repMap, $employees) {
                $repUserId = $repMap[$c->customer_id] ?? null;
                $emp = $repUserId ? ($employees[$repUserId] ?? null) : null;
                $c->sales_rep_name = $emp->sales_rep_name ?? '';
                $c->sales_rep_id = $emp->emp_id ?? null;
                return $c;
            })->keyBy('customer_id');

        $routeQuery = DB::table('route_customers')
            ->whereNull('route_customers.deleted_at')
            ->join('routes', 'route_customers.route_id', '=', 'routes.id')
            ->whereNull('routes.deleted_at')
            ->leftJoin('sales_territories', 'routes.sales_territory_id', '=', 'sales_territories.id')
            ->whereNull('sales_territories.deleted_at')
            ->whereIn('route_customers.customer_id', $customerIds)
            ->where('route_customers.is_active', true)
            ->select(
                'route_customers.customer_id',
                'routes.name_ar as route_name',
                'sales_territories.name_ar as territory_name'
            );

        if ($territoryId) {
            $routeQuery->where('routes.sales_territory_id', $territoryId);
        }

        if ($routeId) {
            $routeQuery->where('route_customers.route_id', $routeId);
        }

        $routeRows = $routeQuery->get();
        $routeMap = [];
        foreach ($routeRows as $r) {
            if (!isset($routeMap[$r->customer_id])) {
                $routeMap[$r->customer_id] = [
                    'route_name' => $r->route_name ?? '',
                    'territory_name' => $r->territory_name ?? '',
                ];
            }
        }

        if ($territoryId || $routeId) {
            $filteredIds = array_keys($routeMap);
            $customerIds = $customerIds->filter(fn($id) => in_array($id, $filteredIds));
            $salesRows = $salesRows->filter(fn($s) => $customerIds->contains($s->customer_id));
            $customers = $customers->filter(fn($c) => $customerIds->contains($c->customer_id));
        }

        $visitItemsMap = [];
        $invoiceQuery = DB::table('sales_invoices')
            ->join('sales_invoice_items', 'sales_invoices.id', '=', 'sales_invoice_items.sales_invoice_id')
            ->join('items', 'sales_invoice_items.item_id', '=', 'items.id')
            ->where('sales_invoices.company_id', $companyId)
            ->whereDate('sales_invoices.invoice_date', '>=', $dateFrom)
            ->whereDate('sales_invoices.invoice_date', '<=', $dateTo)
            ->where('sales_invoices.status', 'posted')
            ->whereNull('sales_invoices.deleted_at')
            ->whereNull('sales_invoice_items.deleted_at')
            ->whereNull('items.deleted_at')
            ->whereIn('sales_invoices.customer_id', $customerIds);

        if ($salesRepId) {
            $invoiceQuery->where('sales_invoices.sales_rep_id', $salesRepId);
        }

        $invoiceItems = $invoiceQuery->select(
            'sales_invoices.customer_id',
            DB::raw('DATE(sales_invoices.invoice_date) as visit_date'),
            'items.id as item_id',
            'items.code as item_code',
            'items.name_ar as item_name',
            DB::raw('SUM(sales_invoice_items.qty) as qty'),
            DB::raw('AVG(sales_invoice_items.price) as price'),
            DB::raw('SUM(sales_invoice_items.net_amount) as total')
        )
            ->groupBy('sales_invoices.customer_id', DB::raw('DATE(sales_invoices.invoice_date)'), 'items.id', 'items.code', 'items.name_ar')
            ->orderBy('items.name_ar')
            ->get();

        foreach ($invoiceItems as $item) {
            $visitItemsMap[$item->customer_id][$item->visit_date][] = [
                'item_code' => $item->item_code,
                'item_name' => $item->item_name,
                'qty'       => round($item->qty, 2),
                'price'     => round($item->price, 2),
                'total'     => round($item->total, 2),
            ];
        }

        $allItemsMap = [];
        foreach ($visitItemsMap as $cid => $visits) {
            foreach ($visits as $items) {
                foreach ($items as $item) {
                    if (!isset($allItemsMap[$cid][$item['item_code']])) {
                        $allItemsMap[$cid][$item['item_code']] = [
                            'item_code' => $item['item_code'],
                            'item_name' => $item['item_name'],
                            'qty' => 0,
                            'price' => $item['price'],
                            'total' => 0,
                        ];
                    }
                    $allItemsMap[$cid][$item['item_code']]['qty'] += $item['qty'];
                    $allItemsMap[$cid][$item['item_code']]['total'] += $item['total'];
                }
            }
        }

        $result = [];
        foreach ($salesRows as $sale) {
            $cid = $sale->customer_id;
            $cust = $customers[$cid] ?? null;
            $route = $routeMap[$cid] ?? [];

            $visits = [];
            $visitDates = array_keys($visitItemsMap[$cid] ?? []);
            sort($visitDates);

            $totalQty = 0;
            $custInvoiceTotal = 0.0;
            $custDiscount = 0.0;
            $custNetTotal = 0.0;
            foreach ($visitDates as $vDate) {
                $vItems = $visitItemsMap[$cid][$vDate] ?? [];
                $vTotalSales = 0;
                $vTotalQty = 0;
                foreach ($vItems as $vi) {
                    $vTotalSales += $vi['total'];
                    $vTotalQty += $vi['qty'];
                }
                $totalQty += $vTotalQty;

                $dayRow = $dayTotals[$cid . '|' . $vDate]->first() ?? null;
                $vInvoiceTotal = $dayRow ? (float) $dayRow->invoice_total : $vTotalSales;
                $vDiscount = $dayRow ? (float) $dayRow->discount : 0.0;
                $vNetTotal = $dayRow ? (float) $dayRow->net_total : $vTotalSales;

                $custInvoiceTotal += $vInvoiceTotal;
                $custDiscount += $vDiscount;
                $custNetTotal += $vNetTotal;

                $visits[] = [
                    'visit_date'    => $vDate,
                    'total_sales'   => round($vTotalSales, 2),
                    'total_qty'     => round($vTotalQty, 2),
                    'invoice_total' => round($vInvoiceTotal, 2),
                    'discount'      => round($vDiscount, 2),
                    'net_total'     => round($vNetTotal, 2),
                    'invoices'      => $dayRow ? (int) $dayRow->invoices : 0,
                    'items'         => $vItems,
                ];
            }

            $result[] = [
                'customer_id'    => $cid,
                'customer_code'  => $cust->customer_code ?? '',
                'customer_name'  => $cust->customer_name ?? '',
                'sales_rep_name' => $cust->sales_rep_name ?? '',
                'territory_name' => $route['territory_name'] ?? '',
                'route_name'     => $route['route_name'] ?? '',
                'total_sales'    => round((float) $sale->total_sales, 2),
                'total_cash'     => round((float) $sale->total_cash, 2),
                'total_qty'      => round($totalQty, 2),
                'invoice_total'  => round($custInvoiceTotal, 2),
                'discount_total' => round($custDiscount, 2),
                'net_total'      => round($custNetTotal, 2),
                'items'          => array_values($allItemsMap[$cid] ?? []),
                'visits'         => $visits,
            ];
        }

        usort($result, fn($a, $b) => strcmp($a['customer_name'], $b['customer_name']));

        return response()->json([
            'data' => [
                'customers' => $result,
            ],
        ]);
    }

    /**
     * GET /api/reports/discount-customers
     * عملاء الخصم - سطر لكل (عميل + يوم): الأصناف المباعة، إجمالي الكمية،
     * إجمالي الفاتورة، الخصم، الصافي.
     * نفس منطق عدّادات الداشبورد: الفواتير التي فيها خصم (بند + فاتورة) وغير ملغاة.
     * فلاتر: date_from / date_to (افتراضي: اليوم)، customer_id، sales_rep_id
     */
    public function discountCustomers(Request $request)
    {
        $request->validate([
            'date_from'    => 'nullable|date',
            'date_to'      => 'nullable|date|after_or_equal:date_from',
            'customer_id'  => 'nullable|integer',
            'sales_rep_id' => 'nullable|integer',
        ]);

        $companyId = $request->user()->company_id;
        $dateFrom = $request->input('date_from') ?? now()->toDateString();
        $dateTo = $request->input('date_to') ?? $dateFrom;
        $discountExpr = 'COALESCE(si.item_discount_total, 0) + COALESCE(si.invoice_discount_total, 0)';

        $invoicesQuery = DB::table('sales_invoices as si')
            ->join('customers as c', 'c.id', '=', 'si.customer_id')
            ->whereNull('c.deleted_at')
            ->where('si.company_id', $companyId)
            ->whereDate('si.invoice_date', '>=', $dateFrom)
            ->whereDate('si.invoice_date', '<=', $dateTo)
            ->whereNull('si.deleted_at')
            ->where('si.status', '!=', 'cancelled')
            ->whereRaw("$discountExpr > 0")
            ->select(
                'si.id as invoice_id',
                'si.customer_id',
                'si.invoice_date',
                'si.sales_rep_id',
                'c.code as customer_code',
                'c.name_ar as customer_name',
                DB::raw("$discountExpr as discount"),
                'si.subtotal',
                'si.tax_total',
                'si.net_total'
            );

        if ($request->filled('customer_id')) {
            $invoicesQuery->where('si.customer_id', $request->input('customer_id'));
        }
        if ($request->filled('sales_rep_id')) {
            $invoicesQuery->where('si.sales_rep_id', $request->input('sales_rep_id'));
        }

        $invoices = $invoicesQuery->get();

        $empty = [
            'rows' => [],
            'summary' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'customers' => 0,
                'invoices' => 0,
                'total_qty' => 0,
                'invoice_total' => 0,
                'discount' => 0,
                'net_total' => 0,
            ],
        ];

        if ($invoices->isEmpty()) {
            return response()->json(['data' => $empty]);
        }

        $itemRows = DB::table('sales_invoice_items as sii')
            ->join('items as i', 'i.id', '=', 'sii.item_id')
            ->whereNull('sii.deleted_at')
            ->whereNull('i.deleted_at')
            ->whereIn('sii.sales_invoice_id', $invoices->pluck('invoice_id'))
            ->groupBy('sii.sales_invoice_id', 'i.id', 'i.code', 'i.name_ar')
            ->select(
                'sii.sales_invoice_id',
                'i.id as item_id',
                'i.code as item_code',
                'i.name_ar as item_name',
                DB::raw('SUM(sii.qty) as qty')
            )
            ->get()
            ->groupBy('sales_invoice_id');

        $rows = [];
        foreach ($invoices as $inv) {
            $date = substr((string) $inv->invoice_date, 0, 10);
            $key = $inv->customer_id . '|' . $date;

            if (!isset($rows[$key])) {
                $rows[$key] = [
                    'customer_id' => (int) $inv->customer_id,
                    'customer_code' => (string) $inv->customer_code,
                    'customer_name' => (string) $inv->customer_name,
                    'invoice_date' => $date,
                    'invoice_count' => 0,
                    'invoice_total' => 0.0,
                    'discount' => 0.0,
                    'net_total' => 0.0,
                    'total_qty' => 0.0,
                    'items' => [],
                ];
            }

            $rows[$key]['invoice_count']++;
            $rows[$key]['invoice_total'] += (float) $inv->subtotal + (float) $inv->tax_total;
            $rows[$key]['discount'] += (float) $inv->discount;
            $rows[$key]['net_total'] += (float) $inv->net_total;

            foreach ($itemRows[$inv->invoice_id] ?? [] as $item) {
                $itemKey = $item->item_id ?? $item->item_name;
                if (!isset($rows[$key]['items'][$itemKey])) {
                    $rows[$key]['items'][$itemKey] = [
                        'item_id' => (int) ($item->item_id ?? 0),
                        'item_code' => (string) ($item->item_code ?? ''),
                        'item_name' => (string) $item->item_name,
                        'qty' => 0.0,
                    ];
                }
                $rows[$key]['items'][$itemKey]['qty'] += (float) $item->qty;
            }
        }

        $result = [];
        $summary = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'customers' => 0,
            'invoices' => 0,
            'total_qty' => 0.0,
            'invoice_total' => 0.0,
            'discount' => 0.0,
            'net_total' => 0.0,
        ];

        foreach ($rows as $row) {
            $row['items'] = array_values($row['items']);
            usort($row['items'], fn ($a, $b) => strcmp($a['item_name'], $b['item_name']));

            $row['total_qty'] = 0.0;
            foreach ($row['items'] as $item) {
                $row['total_qty'] += $item['qty'];
            }

            $row['invoice_total'] = round($row['invoice_total'], 2);
            $row['discount'] = round($row['discount'], 2);
            $row['net_total'] = round($row['net_total'], 2);
            $row['total_qty'] = round($row['total_qty'], 2);
            foreach ($row['items'] as $i => $item) {
                $row['items'][$i]['qty'] = round($item['qty'], 2);
            }

            $summary['invoices'] += $row['invoice_count'];
            $summary['total_qty'] += $row['total_qty'];
            $summary['invoice_total'] += $row['invoice_total'];
            $summary['discount'] += $row['discount'];
            $summary['net_total'] += $row['net_total'];

            $result[] = $row;
        }

        usort($result, function ($a, $b) {
            return [$a['invoice_date'], $a['customer_name']] <=> [$b['invoice_date'], $b['customer_name']];
        });

        $summary['customers'] = count(array_unique(array_column($result, 'customer_id')));
        $summary['total_qty'] = round($summary['total_qty'], 2);
        $summary['invoice_total'] = round($summary['invoice_total'], 2);
        $summary['discount'] = round($summary['discount'], 2);
        $summary['net_total'] = round($summary['net_total'], 2);

        return response()->json([
            'data' => [
                'rows' => $result,
                'summary' => $summary,
            ],
        ]);
    }

    /**
     * GET /api/reports/rep-daily-sales
     * مبيعات المندوب اليومية - تقرير مبيعات كل مندوب مع العملاء خلال فترة
     */
    public function repDailySales(Request $request)
    {
        $request->validate([
            'date'              => 'nullable|date',
            'date_from'         => 'nullable|date',
            'date_to'           => 'nullable|date|after_or_equal:date_from',
            'territory_id'      => 'nullable|integer',
            'route_id'          => 'nullable|integer',
            'sales_rep_id'      => 'nullable|integer',
        ]);

        $companyId = $request->user()->company_id;
        $dateFrom = $request->input('date_from') ?? $request->input('date');
        $dateTo = $request->input('date_to') ?? $request->input('date');
        $territoryId = $request->input('territory_id');
        $routeId = $request->input('route_id');
        $salesRepId = $request->input('sales_rep_id');

        // 1) sales grouped by sales_rep + customer
        $salesQuery = DB::table('sales_invoices')
            ->where('sales_invoices.company_id', $companyId)
            ->whereDate('sales_invoices.invoice_date', '>=', $dateFrom)
            ->whereDate('sales_invoices.invoice_date', '<=', $dateTo)
            ->where('sales_invoices.status', 'posted')
            ->whereNull('sales_invoices.deleted_at');

        if ($salesRepId) {
            $salesQuery->where('sales_invoices.sales_rep_id', $salesRepId);
        }

        $salesRows = $salesQuery
            ->select(
                'sales_rep_id',
                'customer_id',
                DB::raw('SUM(net_total) as total_sales'),
                DB::raw('SUM(paid_amount) as total_cash')
            )
            ->groupBy('sales_rep_id', 'customer_id')
            ->get();

        if ($salesRows->isEmpty()) {
            return response()->json(['data' => ['reps' => []]]);
        }

        $repIds = $salesRows->pluck('sales_rep_id')->unique()->values();
        $customerIds = $salesRows->pluck('customer_id')->unique()->values();

        // 2) employees (reps)
        $employees = DB::table('employees')
            ->whereNull('employees.deleted_at')
            ->whereIn('employees.user_id', $repIds)
            ->where('employees.company_id', $companyId)
            ->select(
                'employees.user_id',
                DB::raw("TRIM(COALESCE(employees.first_name_ar, '') || ' ' || COALESCE(employees.second_name_ar, '') || ' ' || COALESCE(employees.third_name_ar, '') || ' ' || COALESCE(employees.last_name_ar, '')) as rep_name")
            )
            ->get()
            ->keyBy('user_id');

        // 3) customers
        $customers = DB::table('customers')
            ->whereNull('customers.deleted_at')
            ->whereIn('customers.id', $customerIds)
            ->select(
                'customers.id as customer_id',
                'customers.code as customer_code',
                'customers.name_ar as customer_name'
            )
            ->get()
            ->keyBy('customer_id');

        // 4) routes + territories per customer
        $routeQuery = DB::table('route_customers')
            ->whereNull('route_customers.deleted_at')
            ->join('routes', 'route_customers.route_id', '=', 'routes.id')
            ->whereNull('routes.deleted_at')
            ->leftJoin('sales_territories', 'routes.sales_territory_id', '=', 'sales_territories.id')
            ->whereNull('sales_territories.deleted_at')
            ->whereIn('route_customers.customer_id', $customerIds)
            ->where('route_customers.is_active', true)
            ->select(
                'route_customers.customer_id',
                'routes.name_ar as route_name',
                'sales_territories.name_ar as territory_name'
            );

        if ($territoryId) {
            $routeQuery->where('routes.sales_territory_id', $territoryId);
        }
        if ($routeId) {
            $routeQuery->where('route_customers.route_id', $routeId);
        }

        $routeRows = $routeQuery->get();
        $routeMap = [];
        foreach ($routeRows as $r) {
            if (!isset($routeMap[$r->customer_id])) {
                $routeMap[$r->customer_id] = [
                    'route_name' => $r->route_name ?? '',
                    'territory_name' => $r->territory_name ?? '',
                ];
            }
        }

        // if territory/route filter, reduce customerIds
        if ($territoryId || $routeId) {
            $filteredIds = array_keys($routeMap);
            $customerIds = $customerIds->filter(fn($id) => in_array($id, $filteredIds));
            $salesRows = $salesRows->filter(fn($s) => $customerIds->contains($s->customer_id));
        }

        // 4b) invoice numbers per customer
        $invoiceNosMap = [];
        $invoiceNoRows = DB::table('sales_invoices')
            ->where('sales_invoices.company_id', $companyId)
            ->whereDate('sales_invoices.invoice_date', '>=', $dateFrom)
            ->whereDate('sales_invoices.invoice_date', '<=', $dateTo)
            ->where('sales_invoices.status', 'posted')
            ->whereNull('sales_invoices.deleted_at')
            ->whereIn('sales_invoices.customer_id', $customerIds)
            ->select('customer_id', 'invoice_no')
            ->get();

        foreach ($invoiceNoRows as $row) {
            $invoiceNosMap[$row->customer_id][] = $row->invoice_no;
        }

        // 5) invoice items grouped by customer+date
        $visitItemsMap = [];
        $invoiceItems = DB::table('sales_invoices')
            ->join('sales_invoice_items', 'sales_invoices.id', '=', 'sales_invoice_items.sales_invoice_id')
            ->join('items', 'sales_invoice_items.item_id', '=', 'items.id')
            ->where('sales_invoices.company_id', $companyId)
            ->whereDate('sales_invoices.invoice_date', '>=', $dateFrom)
            ->whereDate('sales_invoices.invoice_date', '<=', $dateTo)
            ->where('sales_invoices.status', 'posted')
            ->whereNull('sales_invoices.deleted_at')
            ->whereNull('sales_invoice_items.deleted_at')
            ->whereNull('items.deleted_at')
            ->whereIn('sales_invoices.customer_id', $customerIds);

        if ($salesRepId) {
            $invoiceItems->where('sales_invoices.sales_rep_id', $salesRepId);
        }

        $invoiceItems = $invoiceItems->select(
            'sales_invoices.customer_id',
            'sales_invoices.sales_rep_id',
            DB::raw('DATE(sales_invoices.invoice_date) as visit_date'),
            'items.id as item_id',
            'items.code as item_code',
            'items.name_ar as item_name',
            DB::raw('SUM(sales_invoice_items.qty) as qty'),
            DB::raw('AVG(sales_invoice_items.price) as price'),
            DB::raw('SUM(sales_invoice_items.net_amount) as total')
        )
            ->groupBy('sales_invoices.customer_id', 'sales_invoices.sales_rep_id', DB::raw('DATE(sales_invoices.invoice_date)'), 'items.id', 'items.code', 'items.name_ar')
            ->orderBy('items.name_ar')
            ->get();

        foreach ($invoiceItems as $item) {
            $visitItemsMap[$item->customer_id][$item->visit_date][] = [
                'item_code' => $item->item_code,
                'item_name' => $item->item_name,
                'qty'       => round($item->qty, 2),
                'price'     => round($item->price, 2),
                'total'     => round($item->total, 2),
            ];
        }

        // 6) aggregate items per customer
        $allItemsMap = [];
        foreach ($visitItemsMap as $cid => $visits) {
            foreach ($visits as $items) {
                foreach ($items as $item) {
                    if (!isset($allItemsMap[$cid][$item['item_code']])) {
                        $allItemsMap[$cid][$item['item_code']] = [
                            'item_code' => $item['item_code'],
                            'item_name' => $item['item_name'],
                            'qty' => 0,
                            'price' => $item['price'],
                            'total' => 0,
                        ];
                    }
                    $allItemsMap[$cid][$item['item_code']]['qty'] += $item['qty'];
                    $allItemsMap[$cid][$item['item_code']]['total'] += $item['total'];
                }
            }
        }

        // 7) build per-customer data
        $customerDataMap = [];
        foreach ($salesRows as $sale) {
            $cid = $sale->customer_id;
            $cust = $customers[$cid] ?? null;

            $visits = [];
            $visitDates = array_keys($visitItemsMap[$cid] ?? []);
            sort($visitDates);
            $totalQty = 0;
            foreach ($visitDates as $vDate) {
                $vItems = $visitItemsMap[$cid][$vDate] ?? [];
                $vTotalSales = 0;
                $vTotalQty = 0;
                foreach ($vItems as $vi) {
                    $vTotalSales += $vi['total'];
                    $vTotalQty += $vi['qty'];
                }
                $totalQty += $vTotalQty;
                $visits[] = [
                    'visit_date'  => $vDate,
                    'total_sales' => round($vTotalSales, 2),
                    'total_qty'   => round($vTotalQty, 2),
                    'items'       => $vItems,
                ];
            }

            $customerDataMap[$cid] = [
                'customer_id'    => $cid,
                'customer_code'  => $cust->customer_code ?? '',
                'customer_name'  => $cust->customer_name ?? '',
                'invoice_nos'    => $invoiceNosMap[$cid] ?? [],
                'territory_name' => $routeMap[$cid]['territory_name'] ?? '',
                'route_name'     => $routeMap[$cid]['route_name'] ?? '',
                'total_sales'    => round((float) $sale->total_sales, 2),
                'total_cash'     => round((float) $sale->total_cash, 2),
                'total_qty'      => round($totalQty, 2),
                'items'          => array_values($allItemsMap[$cid] ?? []),
                'visits'         => $visits,
            ];
        }

        // 8) group by sales rep
        $repGroups = [];
        foreach ($salesRows as $sale) {
            $rid = $sale->sales_rep_id;
            if (!isset($repGroups[$rid])) {
                $emp = $employees[$rid] ?? null;
                // get territory/route from first customer
                $firstCid = $sale->customer_id;
                $repGroups[$rid] = [
                    'rep_id'         => $rid,
                    'rep_name'       => $emp->rep_name ?? '',
                    'territory_name' => $routeMap[$firstCid]['territory_name'] ?? '',
                    'route_name'     => $routeMap[$firstCid]['route_name'] ?? '',
                    'customers'      => [],
                    'total_sales'    => 0,
                    'total_qty'      => 0,
                ];
            }
            $custData = $customerDataMap[$sale->customer_id] ?? null;
            if ($custData) {
                $repGroups[$rid]['customers'][] = $custData;
                $repGroups[$rid]['total_sales'] += $custData['total_sales'];
                $repGroups[$rid]['total_qty'] += $custData['total_qty'];
            }
        }

        $result = array_values($repGroups);
        usort($result, fn($a, $b) => strcmp($a['rep_name'], $b['rep_name']));

        // round totals
        foreach ($result as &$rep) {
            $rep['total_sales'] = round($rep['total_sales'], 2);
            $rep['total_qty'] = round($rep['total_qty'], 2);
            usort($rep['customers'], function ($a, $b) {
                $aInv = is_array($a['invoice_nos'] ?? null) ? ($a['invoice_nos'][0] ?? '') : ($a['invoice_nos'] ?? '');
                $bInv = is_array($b['invoice_nos'] ?? null) ? ($b['invoice_nos'][0] ?? '') : ($b['invoice_nos'] ?? '');
                return strcmp($aInv, $bInv);
            });
        }

        return response()->json([
            'data' => [
                'reps' => $result,
            ],
        ]);
    }

    /**
     * GET /api/reports/customer-sales
     * مبيعات العملاء - تقرير بمبيعات كل عميل مع المنتجات خلال فترة
     */
    public function customerSales(Request $request)
    {
        $request->validate([
            'date_from'    => 'required|date',
            'date_to'      => 'required|date|after_or_equal:date_from',
            'territory_id' => 'nullable|integer',
            'route_id'     => 'nullable|integer',
            'visit_date'   => 'nullable|date',
        ]);

        $companyId = $request->user()->company_id;
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $territoryId = $request->input('territory_id');
        $routeId = $request->input('route_id');
        $visitDate = $request->input('visit_date');

        $query = DB::table('sales_invoices')
            ->join('customers', 'sales_invoices.customer_id', '=', 'customers.id')
            ->whereNull('customers.deleted_at')
            ->leftJoin('route_customers', function ($q) {
                $q->on('route_customers.customer_id', '=', 'customers.id')
                  ->where('route_customers.is_active', true);
            })
            ->whereNull('route_customers.deleted_at')
            ->leftJoin('routes', 'route_customers.route_id', '=', 'routes.id')
            ->whereNull('routes.deleted_at')
            ->leftJoin('sales_territories', 'routes.sales_territory_id', '=', 'sales_territories.id')
            ->whereNull('sales_territories.deleted_at')
            ->leftJoin('employees', 'sales_invoices.sales_rep_id', '=', 'employees.id')
            ->whereNull('employees.deleted_at')
            ->where('sales_invoices.company_id', $companyId)
            ->whereDate('sales_invoices.invoice_date', '>=', $dateFrom)
            ->whereDate('sales_invoices.invoice_date', '<=', $dateTo)
            ->where('sales_invoices.status', 'posted')
            ->whereNull('sales_invoices.deleted_at')
            ->select(
                'customers.id as customer_id',
                'customers.code as customer_code',
                'customers.name_ar as customer_name',
                DB::raw('COALESCE(sales_territories.name_ar, "") as territory_name'),
                DB::raw('COALESCE(routes.name_ar, "") as route_name'),
                DB::raw("COALESCE(employees.first_name_ar, '') || ' ' || COALESCE(employees.second_name_ar, '') || ' ' || COALESCE(employees.last_name_ar, '') as sales_rep_name"),
                DB::raw('SUM(sales_invoices.net_total) as sales')
            )
            ->groupBy(
                'customers.id', 'customers.code', 'customers.name_ar',
                'sales_territories.name_ar', 'routes.name_ar',
                'employees.first_name_ar', 'employees.second_name_ar', 'employees.last_name_ar'
            );

        if ($territoryId) {
            $query->where('routes.sales_territory_id', $territoryId);
        }
        if ($routeId) {
            $query->where('route_customers.route_id', $routeId);
        }
        if ($visitDate) {
            $query->whereDate('sales_invoices.invoice_date', $visitDate);
        }

        $customers = $query->orderBy('customers.name_ar')->get();

        // جلب أصناف كل عميل
        $customerIds = $customers->pluck('customer_id');
        $invoiceQuery = DB::table('sales_invoices')
            ->where('sales_invoices.company_id', $companyId)
            ->whereDate('sales_invoices.invoice_date', '>=', $dateFrom)
            ->whereDate('sales_invoices.invoice_date', '<=', $dateTo)
            ->where('sales_invoices.status', 'posted')
            ->whereNull('sales_invoices.deleted_at');

        if ($customerIds->isNotEmpty()) {
            $invoiceQuery->whereIn('sales_invoices.customer_id', $customerIds);
        }
        if ($territoryId) {
            $invoiceQuery->where('sales_invoices.sales_territory_id', $territoryId);
        }
        if ($routeId) {
            $invoiceQuery->where('sales_invoices.route_id', $routeId);
        }
        if ($visitDate) {
            $invoiceQuery->whereDate('sales_invoices.invoice_date', $visitDate);
        }

        $invoiceIds = $invoiceQuery->pluck('sales_invoices.id');

        $itemsMap = [];
        if ($invoiceIds->isNotEmpty()) {
            $invoiceItems = DB::table('sales_invoice_items')
                ->join('items', 'sales_invoice_items.item_id', '=', 'items.id')
                ->join('sales_invoices', 'sales_invoice_items.sales_invoice_id', '=', 'sales_invoices.id')
                ->whereIn('sales_invoice_items.sales_invoice_id', $invoiceIds)
                ->whereNull('sales_invoice_items.deleted_at')
                ->whereNull('items.deleted_at')
                ->select(
                    'sales_invoices.customer_id',
                    'items.code as item_code',
                    'items.name_ar as item_name',
                    DB::raw('SUM(sales_invoice_items.qty) as qty'),
                    DB::raw('AVG(sales_invoice_items.price) as price'),
                    DB::raw('SUM(sales_invoice_items.net_amount) as total')
                )
                ->groupBy('sales_invoices.customer_id', 'items.code', 'items.name_ar')
                ->orderBy('items.name_ar')
                ->get();

            foreach ($invoiceItems as $item) {
                $itemsMap[$item->customer_id][] = [
                    'item_code' => $item->item_code,
                    'item_name' => $item->item_name,
                    'qty'       => round($item->qty, 2),
                    'price'     => round($item->price, 2),
                    'total'     => round($item->total, 2),
                ];
            }
        }

        // جلب تواريخ الزيارة المتاحة
        $visitDates = DB::table('sales_invoices')
            ->where('sales_invoices.company_id', $companyId)
            ->whereDate('sales_invoices.invoice_date', '>=', $dateFrom)
            ->whereDate('sales_invoices.invoice_date', '<=', $dateTo)
            ->where('sales_invoices.status', 'posted')
            ->whereNull('sales_invoices.deleted_at')
            ->whereNotNull('sales_invoices.customer_id')
            ->select(DB::raw('DISTINCT DATE(sales_invoices.invoice_date) as visit_date'))
            ->orderBy('visit_date')
            ->pluck('visit_date')
            ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'));

        $customers = $customers->map(function ($c) use ($itemsMap) {
            $c->items = $itemsMap[$c->customer_id] ?? [];
            return $c;
        });

        return response()->json([
            'data' => [
                'customers'   => $customers,
                'visit_dates' => $visitDates,
            ],
        ]);
    }

    /**
     * GET /api/reports/warehouse-daily-movement
     * حركة المخزن اليومية
     */
    public function warehouseDailyMovement(Request $request)
    {
        $request->validate([
            'date'         => 'required|date',
            'warehouse_id' => 'nullable|integer',
        ]);

        $companyId = $request->user()->company_id;
        $warehouseId = $request->input('warehouse_id');
        $date = $request->input('date');

        // 1. جلب كل الأصناف النشطة للشركة
        $allItems = \App\Models\Item::where('company_id', $companyId)
            ->where('is_active', true)
            ->with('baseUnit:id,name_ar,name_en')
            ->with('itemCategory:id,name_ar,name_en')
            ->get()
            ->keyBy('id');

        // 2. حساب الرصيد الحالي الفعلي للمخزن (رصيد الصباحي = رصيد المخزن الحالي)
        $currentStockMap = [];

        // 2.1 الأرصدة الافتتاحية المُدخَلة
        $obQuery = \App\Models\InventoryOpeningBalance::where('company_id', $companyId);
        if ($warehouseId) {
            $obQuery->where('warehouse_id', $warehouseId);
        }
        $openingBalances = $obQuery->get();
        foreach ($openingBalances as $ob) {
            $wh = $ob->warehouse_id ?? 0;
            $currentStockMap[$wh][$ob->item_id] = ($currentStockMap[$wh][$ob->item_id] ?? 0) + (float) $ob->qty;
        }

        // 2.2 جميع الحركات المُرحَّلة (قبل التاريخ) لحساب رصيد بداية اليوم
        // يجب أن يتوافق فلتر الحركات مع فلتر الوارد والصادر أدناه لضمان تساوي
        // رصيد المساء لليوم السابق مع رصيد الصباحي لليوم الحالي
        $allTxQuery = \App\Models\InventoryTransaction::where('company_id', $companyId)
            ->where('status', 'posted')
            ->whereDate('transaction_date', '<', $date)
            ->whereHas('transactionType', function ($q) {
                $q->where(function ($sub) {
                    // additions: استلام مشتريات فقط (يتوافق مع فلتر الوارد)
                    $sub->where('effect', 'addition')
                        ->where('code', 'PURCHASE_RECEIPT');
                })->orWhere(function ($sub) {
                    // subtractions: مبيعات فقط (يتوافق مع فلتر الصادر)
                    $sub->where('effect', 'subtraction')
                        ->where('code', 'SALES_INVOICE');
                });
            })
            ->with('transactionType:id,effect,code')
            ->with('items:id,inventory_transaction_id,item_id,qty');
        if ($warehouseId) {
            $allTxQuery->where('warehouse_id', $warehouseId);
        }
        foreach ($allTxQuery->get() as $txn) {
            $effect = $txn->transactionType?->effect;
            $sign = $effect === 'addition' ? 1 : ($effect === 'subtraction' ? -1 : 0);
            if ($sign === 0) continue;
            $wh = $txn->warehouse_id ?? 0;
            foreach ($txn->items as $it) {
                $currentStockMap[$wh][$it->item_id] = ($currentStockMap[$wh][$it->item_id] ?? 0) + $sign * abs((float) $it->qty);
            }
        }

        // 2.3 تسطيح الرصيد لكل صنف (مجموع كل المستودعات أو مستودع محدد)
        $currentStockPerItem = [];
        foreach ($currentStockMap as $wh => $items) {
            foreach ($items as $itemId => $qty) {
                $currentStockPerItem[$itemId] = ($currentStockPerItem[$itemId] ?? 0) + $qty;
            }
        }

        // 3. حركات اليوم - الوارد
        $inQuery = \App\Models\InventoryTransaction::where('company_id', $companyId)
            ->whereDate('transaction_date', $date)
            ->where('status', 'posted')
            ->whereHas('transactionType', fn($q) => $q->where('effect', 'addition')->where('code', 'PURCHASE_RECEIPT'))
            ->with('items:id,inventory_transaction_id,item_id,qty,unit_cost');
        if ($warehouseId) {
            $inQuery->where('warehouse_id', $warehouseId);
        }
        $inTransactions = $inQuery->get();

        // 4.1 سعر الشراء من الوحدة الافتراضية في item_units
        $itemIds = $allItems->keys()->all();
        $defaultUnitCostMap = [];
        $unitRows = \App\Models\ItemUnit::whereIn('item_id', $itemIds)
            ->where('is_default', true)
            ->get(['item_id', 'purchase_price']);
        foreach ($unitRows as $u) {
            $cost = (float) $u->purchase_price;
            if ($cost > 0) {
                $defaultUnitCostMap[$u->item_id] = $cost;
            }
        }

        // 4.2 حركات اليوم - الوارد (تجميع)
        $inQtyMap = [];
        foreach ($inTransactions as $txn) {
            foreach ($txn->items as $item) {
                $itemId = $item->item_id;
                $inQtyMap[$itemId] = ($inQtyMap[$itemId] ?? 0) + abs((float) $item->qty);
            }
        }

        // 4.3 حركات اليوم - الصادر (مبيعات فقط) من فواتير المبيعات مباشرة
        // لضمان التوافق مع تقرير مبيعات المندوبين (sales_invoice_items.qty)
        $outQtyMap = [];
        $salesOutQuery = DB::table('sales_invoices')
            ->join('sales_invoice_items', 'sales_invoices.id', '=', 'sales_invoice_items.sales_invoice_id')
            ->where('sales_invoices.company_id', $companyId)
            ->whereDate('sales_invoices.invoice_date', $date)
            ->where('sales_invoices.status', 'posted')
            ->whereNull('sales_invoices.deleted_at')
            ->whereNull('sales_invoice_items.deleted_at')
            ->select(
                'sales_invoice_items.item_id',
                DB::raw('ABS(sales_invoice_items.qty) as qty')
            );
        if ($warehouseId) {
            $salesOutQuery->where(function ($q) use ($warehouseId) {
                $q->where('sales_invoice_items.warehouse_id', $warehouseId)
                  ->orWhereNull('sales_invoice_items.warehouse_id');
            });
        }
        foreach ($salesOutQuery->get() as $row) {
            $itemId = $row->item_id;
            $outQtyMap[$itemId] = ($outQtyMap[$itemId] ?? 0) + abs((float) $row->qty);
        }

        // 4.3 مبيعات الهاند هيلد من جميع الأيام السابقة (تُضاف للرصيد الصباحي)

        // أولاً: إضافة المبيعات ERP السابقة من المعاملات المخزنية
        // (لإلغاء تأثيرها لأن currentStockPerItem تشملها بـ inventory_transaction_items.qty)
        $priorErpSalesQtyMap = [];
        $priorErpSalesQuery = \App\Models\InventoryTransaction::where('company_id', $companyId)
            ->where('status', 'posted')
            ->whereDate('transaction_date', '<', $date)
            ->whereHas('transactionType', fn($q) => $q->where('effect', 'subtraction')->where('code', 'SALES_INVOICE'))
            ->with('items:id,inventory_transaction_id,item_id,qty');
        if ($warehouseId) {
            $priorErpSalesQuery->where('warehouse_id', $warehouseId);
        }
        foreach ($priorErpSalesQuery->get() as $txn) {
            foreach ($txn->items as $it) {
                $itemId = $it->item_id;
                $priorErpSalesQtyMap[$itemId] = ($priorErpSalesQtyMap[$itemId] ?? 0) + abs((float) $it->qty);
            }
        }

        // ثانياً: جمع كل المبيعات السابقة من فواتير المبيعات (ERP + موبايل)
        // لتخصم بالكمية الصحيحة (sales_invoice_items.qty)
        $allPriorSalesQtyMap = [];
        $allPriorSalesQuery = DB::table('sales_invoices')
            ->join('sales_invoice_items', 'sales_invoices.id', '=', 'sales_invoice_items.sales_invoice_id')
            ->where('sales_invoices.company_id', $companyId)
            ->whereDate('sales_invoices.invoice_date', '<', $date)
            ->where('sales_invoices.status', 'posted')
            ->whereNull('sales_invoices.deleted_at')
            ->whereNull('sales_invoice_items.deleted_at')
            ->select(
                'sales_invoice_items.item_id',
                DB::raw('ABS(sales_invoice_items.qty) as qty')
            );
        if ($warehouseId) {
            $allPriorSalesQuery->where(function ($q) use ($warehouseId) {
                $q->where('sales_invoice_items.warehouse_id', $warehouseId)
                  ->orWhereNull('sales_invoice_items.warehouse_id');
            });
        }
        foreach ($allPriorSalesQuery->get() as $row) {
            $itemId = $row->item_id;
            $allPriorSalesQtyMap[$itemId] = ($allPriorSalesQtyMap[$itemId] ?? 0) + abs((float) $row->qty);
        }

        // 6. بناء النتيجة لكل صنف
        $result = [];
        foreach ($allItems as $itemId => $item) {
            // الرصيد الصباحي = رصيد المخزن + مبيعات ERP السابقة (إلغاء) - كل المبيعات السابقة (بالكمية الصحيحة)
            // هذا يضمن توافق رصيد الصباح مع طريقة حساب الصادر من فواتير المبيعات
            $openingBalance = max(0,
                ($currentStockPerItem[$itemId] ?? 0)
                + ($priorErpSalesQtyMap[$itemId] ?? 0)
                - ($allPriorSalesQtyMap[$itemId] ?? 0)
            );
            $inQty = $inQtyMap[$itemId] ?? 0;
            $outQty = $outQtyMap[$itemId] ?? 0;
            $total = $openingBalance + $inQty;
            $closingBalance = $total - $outQty;
            $unitCost = $defaultUnitCostMap[$itemId] ?? 0;
            $totalValue = $closingBalance * $unitCost;

            $result[] = [
                'name'            => $item->name_ar ?? $item->name_en ?? '',
                'code'            => $item->code ?? '',
                'unit'            => $item->baseUnit?->name_ar ?? $item->baseUnit?->name_en ?? '',
                'category'        => $item->itemCategory?->name_ar ?? $item->itemCategory?->name_en ?? '',
                'opening_balance' => $openingBalance,
                'incoming'        => $inQty,
                'total'           => $total,
                'outgoing'        => $outQty,
                'closing_balance' => $closingBalance,
                'total_value'     => $totalValue,
                'unit_cost'       => $unitCost,
            ];
        }

        return response()->json([
            'data' => [
                'items' => $result,
            ],
        ]);
    }

    /**
     * GET /api/reports/warehouse-monthly-movement
     * تقرير حركة المخزون الشهرية من تاريخ الى تاريخ
     */
    public function warehouseMonthlyMovement(Request $request)
    {
        $request->validate([
            'date_from'     => 'required|date',
            'date_to'       => 'required|date|after_or_equal:date_from',
            'warehouse_id'  => 'nullable|integer',
        ]);

        $companyId = $request->user()->company_id;
        $warehouseId = $request->input('warehouse_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // 1. جلب كل الأصناف النشطة
        $allItems = \App\Models\Item::where('company_id', $companyId)
            ->where('is_active', true)
            ->with('baseUnit:id,name_ar,name_en')
            ->with('itemCategory:id,name_ar,name_en')
            ->get()
            ->keyBy('id');

        // 2. حساب رصيد بداية الفترة (قبل date_from)
        $currentStockMap = [];

        $obQuery = \App\Models\InventoryOpeningBalance::where('company_id', $companyId);
        if ($warehouseId) {
            $obQuery->where('warehouse_id', $warehouseId);
        }
        foreach ($obQuery->get() as $ob) {
            $wh = $ob->warehouse_id ?? 0;
            $currentStockMap[$wh][$ob->item_id] = ($currentStockMap[$wh][$ob->item_id] ?? 0) + (float) $ob->qty;
        }

        $allTxQuery = \App\Models\InventoryTransaction::where('company_id', $companyId)
            ->where('status', 'posted')
            ->whereDate('transaction_date', '<', $dateFrom)
            ->whereHas('transactionType', function ($q) {
                $q->where(function ($sub) {
                    $sub->where('effect', 'addition')
                        ->where('code', 'PURCHASE_RECEIPT');
                })->orWhere(function ($sub) {
                    $sub->where('effect', 'subtraction')
                        ->where('code', 'SALES_INVOICE');
                });
            })
            ->with('transactionType:id,effect,code')
            ->with('items:id,inventory_transaction_id,item_id,qty');
        if ($warehouseId) {
            $allTxQuery->where('warehouse_id', $warehouseId);
        }
        foreach ($allTxQuery->get() as $txn) {
            $effect = $txn->transactionType?->effect;
            $sign = $effect === 'addition' ? 1 : ($effect === 'subtraction' ? -1 : 0);
            if ($sign === 0) continue;
            $wh = $txn->warehouse_id ?? 0;
            foreach ($txn->items as $it) {
                $currentStockMap[$wh][$it->item_id] = ($currentStockMap[$wh][$it->item_id] ?? 0) + $sign * abs((float) $it->qty);
            }
        }

        $currentStockPerItem = [];
        foreach ($currentStockMap as $wh => $items) {
            foreach ($items as $itemId => $qty) {
                $currentStockPerItem[$itemId] = ($currentStockPerItem[$itemId] ?? 0) + $qty;
            }
        }

        // 3. حركات الفترة - الوارد
        $inQuery = \App\Models\InventoryTransaction::where('company_id', $companyId)
            ->whereDate('transaction_date', '>=', $dateFrom)
            ->whereDate('transaction_date', '<=', $dateTo)
            ->where('status', 'posted')
            ->whereHas('transactionType', fn($q) => $q->where('effect', 'addition')->where('code', 'PURCHASE_RECEIPT'))
            ->with('items:id,inventory_transaction_id,item_id,qty,unit_cost,from_location_type,from_location_id');
        if ($warehouseId) {
            $inQuery->where('warehouse_id', $warehouseId);
        }
        $inTransactions = $inQuery->get();

        // 3.1 تحديد المورد لكل حركة مشتريات (لعرض المشتريات باسم المورد)
        $invoiceSupplierMap = [];
        $invoiceIds = $inTransactions
            ->filter(fn ($txn) => $txn->reference_type === \App\Models\PurchaseInvoice::class)
            ->pluck('reference_id')->filter()->unique()->values()->all();
        if (!empty($invoiceIds)) {
            foreach (\App\Models\PurchaseInvoice::whereIn('id', $invoiceIds)->get(['id', 'supplier_id']) as $inv) {
                $invoiceSupplierMap[$inv->id] = $inv->supplier_id;
            }
        }

        $receiptSupplierMap = [];
        $receiptIds = $inTransactions
            ->filter(fn ($txn) => $txn->reference_type === \App\Models\PurchaseReceipt::class)
            ->pluck('reference_id')->filter()->unique()->values()->all();
        if (!empty($receiptIds)) {
            foreach (\App\Models\PurchaseReceipt::whereIn('id', $receiptIds)->get(['id', 'supplier_id']) as $rec) {
                $receiptSupplierMap[$rec->id] = $rec->supplier_id;
            }
        }

        $resolveSupplierId = function ($txn, $item) use ($invoiceSupplierMap, $receiptSupplierMap) {
            if (($item->from_location_type ?? null) === 'supplier' && !empty($item->from_location_id)) {
                return (int) $item->from_location_id;
            }
            if ($txn->reference_type === \App\Models\PurchaseInvoice::class) {
                $supplierId = $invoiceSupplierMap[$txn->reference_id] ?? null;
                return $supplierId ? (int) $supplierId : null;
            }
            if ($txn->reference_type === \App\Models\PurchaseReceipt::class) {
                $supplierId = $receiptSupplierMap[$txn->reference_id] ?? null;
                return $supplierId ? (int) $supplierId : null;
            }
            return null;
        };

        $itemIds = $allItems->keys()->all();
        $defaultUnitCostMap = [];
        $unitRows = \App\Models\ItemUnit::whereIn('item_id', $itemIds)
            ->where('is_default', true)
            ->get(['item_id', 'purchase_price']);
        foreach ($unitRows as $u) {
            $cost = (float) $u->purchase_price;
            if ($cost > 0) {
                $defaultUnitCostMap[$u->item_id] = $cost;
            }
        }

        $inQtyMap = [];
        $inQtyBySupplierMap = [];
        foreach ($inTransactions as $txn) {
            foreach ($txn->items as $item) {
                $itemId = $item->item_id;
                $qty = abs((float) $item->qty);
                $inQtyMap[$itemId] = ($inQtyMap[$itemId] ?? 0) + $qty;
                $supplierId = $resolveSupplierId($txn, $item) ?? 0;
                $inQtyBySupplierMap[$itemId][$supplierId] = ($inQtyBySupplierMap[$itemId][$supplierId] ?? 0) + $qty;
            }
        }

        // 3.2 الموردين الذين توجد لهم مشتريات خلال الفترة (لعرض المشتريات باسم المورد)
        $supplierTotals = [];
        foreach ($inQtyBySupplierMap as $perSupplier) {
            foreach ($perSupplier as $supplierId => $qty) {
                $supplierTotals[$supplierId] = ($supplierTotals[$supplierId] ?? 0) + $qty;
            }
        }
        $supplierIds = collect(array_keys($supplierTotals))->filter(fn ($id) => (int) $id > 0)->values()->all();
        $supplierNames = [];
        if (!empty($supplierIds)) {
            foreach (\App\Models\Supplier::whereIn('id', $supplierIds)->get(['id', 'supplier_name']) as $sup) {
                $supplierNames[$sup->id] = $sup->supplier_name;
            }
        }

        $purchases = [];
        foreach ($supplierTotals as $supplierId => $totalQty) {
            $supplierId = (int) $supplierId;
            $purchases[] = [
                'supplier_id'   => $supplierId,
                'supplier_name' => $supplierNames[$supplierId] ?? ($supplierId > 0 ? 'مورد #' . $supplierId : 'بدون مورد'),
                'total_qty'     => (float) $totalQty,
            ];
        }
        usort($purchases, fn ($a, $b) => strcmp($a['supplier_name'], $b['supplier_name']));

        // 4. حركات الفترة - الصادر
        $outQtyMap = [];
        $salesOutQuery = DB::table('sales_invoices')
            ->join('sales_invoice_items', 'sales_invoices.id', '=', 'sales_invoice_items.sales_invoice_id')
            ->where('sales_invoices.company_id', $companyId)
            ->whereDate('sales_invoices.invoice_date', '>=', $dateFrom)
            ->whereDate('sales_invoices.invoice_date', '<=', $dateTo)
            ->where('sales_invoices.status', 'posted')
            ->whereNull('sales_invoices.deleted_at')
            ->whereNull('sales_invoice_items.deleted_at')
            ->select(
                'sales_invoice_items.item_id',
                DB::raw('ABS(sales_invoice_items.qty) as qty')
            );
        if ($warehouseId) {
            $salesOutQuery->where(function ($q) use ($warehouseId) {
                $q->where('sales_invoice_items.warehouse_id', $warehouseId)
                  ->orWhereNull('sales_invoice_items.warehouse_id');
            });
        }
        foreach ($salesOutQuery->get() as $row) {
            $itemId = $row->item_id;
            $outQtyMap[$itemId] = ($outQtyMap[$itemId] ?? 0) + abs((float) $row->qty);
        }

        // 5. مبيعات سابقة (للحساب مع الرصيد الصباحي)
        $priorErpSalesQtyMap = [];
        $priorErpSalesQuery = \App\Models\InventoryTransaction::where('company_id', $companyId)
            ->where('status', 'posted')
            ->whereDate('transaction_date', '<', $dateFrom)
            ->whereHas('transactionType', fn($q) => $q->where('effect', 'subtraction')->where('code', 'SALES_INVOICE'))
            ->with('items:id,inventory_transaction_id,item_id,qty');
        if ($warehouseId) {
            $priorErpSalesQuery->where('warehouse_id', $warehouseId);
        }
        foreach ($priorErpSalesQuery->get() as $txn) {
            foreach ($txn->items as $it) {
                $priorErpSalesQtyMap[$it->item_id] = ($priorErpSalesQtyMap[$it->item_id] ?? 0) + abs((float) $it->qty);
            }
        }

        $allPriorSalesQtyMap = [];
        $allPriorSalesQuery = DB::table('sales_invoices')
            ->join('sales_invoice_items', 'sales_invoices.id', '=', 'sales_invoice_items.sales_invoice_id')
            ->where('sales_invoices.company_id', $companyId)
            ->whereDate('sales_invoices.invoice_date', '<', $dateFrom)
            ->where('sales_invoices.status', 'posted')
            ->whereNull('sales_invoices.deleted_at')
            ->whereNull('sales_invoice_items.deleted_at')
            ->select(
                'sales_invoice_items.item_id',
                DB::raw('ABS(sales_invoice_items.qty) as qty')
            );
        if ($warehouseId) {
            $allPriorSalesQuery->where(function ($q) use ($warehouseId) {
                $q->where('sales_invoice_items.warehouse_id', $warehouseId)
                  ->orWhereNull('sales_invoice_items.warehouse_id');
            });
        }
        foreach ($allPriorSalesQuery->get() as $row) {
            $allPriorSalesQtyMap[$row->item_id] = ($allPriorSalesQtyMap[$row->item_id] ?? 0) + abs((float) $row->qty);
        }

        // 6. بناء النتيجة
        $result = [];
        foreach ($allItems as $itemId => $item) {
            $openingBalance = max(0,
                ($currentStockPerItem[$itemId] ?? 0)
                + ($priorErpSalesQtyMap[$itemId] ?? 0)
                - ($allPriorSalesQtyMap[$itemId] ?? 0)
            );
            $inQty = $inQtyMap[$itemId] ?? 0;
            $outQty = $outQtyMap[$itemId] ?? 0;
            $total = $openingBalance + $inQty;
            $closingBalance = $total - $outQty;
            $unitCost = $defaultUnitCostMap[$itemId] ?? 0;
            $totalValue = $closingBalance * $unitCost;

            $result[] = [
                'name'            => $item->name_ar ?? $item->name_en ?? '',
                'code'            => $item->code ?? '',
                'unit'            => $item->baseUnit?->name_ar ?? $item->baseUnit?->name_en ?? '',
                'category'        => $item->itemCategory?->name_ar ?? $item->itemCategory?->name_en ?? '',
                'opening_balance' => $openingBalance,
                'incoming'        => $inQty,
                'total'           => $total,
                'outgoing'        => $outQty,
                'closing_balance' => $closingBalance,
                'total_value'     => $totalValue,
                'unit_cost'       => $unitCost,
                'incoming_by_supplier' => (object) array_map('floatval', $inQtyBySupplierMap[$itemId] ?? []),
            ];
        }

        return response()->json([
            'data' => [
                'items'     => $result,
                'purchases' => $purchases,
            ],
        ]);
    }

    /**
     * GET /api/reports/daily-product-sales
     * تقرير مبيعات يومية بالصنف (كل يوم صف وكل صنف عمود)
     */
    public function dailyProductSales(Request $request)
    {
        $request->validate([
            'date_from'    => 'required|date',
            'date_to'      => 'required|date|after_or_equal:date_from',
        ]);

        $companyId = $request->user()->company_id;
        $dateFrom = $request->input('date_from') . ' 00:00:00';
        $dateTo = $request->input('date_to') . ' 23:59:59';

        // استعلام واحد يجمع كل حاجة
        $salesRows = DB::table('sales_invoices as si')
            ->join('sales_invoice_items as sii', 'sii.sales_invoice_id', '=', 'si.id')
            ->join('items as it', 'it.id', '=', 'sii.item_id')
            ->where('si.company_id', $companyId)
            ->where('si.status', 'posted')
            ->whereNull('si.deleted_at')
            ->whereBetween('si.invoice_date', [$dateFrom, $dateTo])
            ->whereNull('sii.deleted_at')
            ->whereNull('it.deleted_at')
            ->select(
                'sii.item_id',
                DB::raw('DATE(si.invoice_date) as sale_date'),
                DB::raw('ABS(SUM(COALESCE(NULLIF(sii.base_quantity, 0), sii.qty))) as total_qty'),
                DB::raw('ABS(SUM(sii.net_amount)) as total_amount')
            )
            ->groupBy('sii.item_id', DB::raw('DATE(si.invoice_date)'))
            ->get();

        // استخراج IDs الأصناف من النتيجة مباشرة
        $soldItemIds = $salesRows->pluck('item_id')->unique()->values()->all();

        $items = [];
        if (!empty($soldItemIds)) {
            $items = \App\Models\Item::whereIn('id', $soldItemIds)
                ->get(['id', 'name_ar', 'name_en', 'code'])
                ->keyBy('id');
            // تجاهل أصناف محذوفة حتى لا تظهر أعمدة فارغة
            $soldItemIds = $items->keys()->values()->all();
        }

        // بناء بيانات pivot
        $pivot = [];
        foreach ($salesRows as $row) {
            $date = $row->sale_date;
            $itemId = $row->item_id;
            $pivot[$date][$itemId] = [
                'qty' => (float) $row->total_qty,
                'amount' => (float) $row->total_amount,
            ];
        }

        // جميع الأيام
        $allDays = [];
        $current = new \Carbon\Carbon($request->input('date_from'));
        $end = new \Carbon\Carbon($request->input('date_to'));
        while ($current->lte($end)) {
            $allDays[] = $current->toDateString();
            $current->addDay();
        }

        // بناء الاستجابة
        $result = [
            'items' => $items->values()->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name_ar ?? $item->name_en ?? '',
                'code' => $item->code ?? '',
            ])->toArray(),
            'days' => [],
        ];

        foreach ($allDays as $date) {
            $dayData = [
                'date' => $date,
                'products' => [],
                'day_total_qty' => 0,
                'day_total_amount' => 0,
            ];
            foreach ($soldItemIds as $itemId) {
                $dayData['products'][$itemId] = [
                    'qty' => $pivot[$date][$itemId]['qty'] ?? 0,
                    'amount' => $pivot[$date][$itemId]['amount'] ?? 0,
                ];
                $dayData['day_total_qty'] += $dayData['products'][$itemId]['qty'];
                $dayData['day_total_amount'] += $dayData['products'][$itemId]['amount'];
            }
            $result['days'][] = $dayData;
        }

        return response()->json(['data' => $result]);
    }

    /**
     * GET /api/reports/rep-movement-by-item
     * تقرير حركة المندوب بالصنف
     */
    public function repMovementByItem(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'date_from'   => 'nullable|date',
            'date_to'     => 'nullable|date',
        ]);

        $companyId = $request->user()->company_id;
        $employeeId = (int) $request->input('user_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // 1. التحميل (Load) — من أوامر التحميل
        $loadQuery = DB::table('load_request_items')
            ->join('load_requests', 'load_request_items.load_request_id', '=', 'load_requests.id')
            ->whereNull('load_requests.deleted_at')
            ->where('load_requests.user_id', $employeeId)
            ->where('load_requests.company_id', $companyId)
            ->whereIn('load_requests.status', ['approved', 'loading', 'completed'])
            ->select(
                'load_request_items.item_id',
                DB::raw('SUM(load_request_items.quantity) as load_qty')
            )
            ->groupBy('load_request_items.item_id');

        if ($dateFrom) {
            $loadQuery->where('load_requests.request_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $loadQuery->where('load_requests.request_date', '<=', $dateTo);
        }
        $loadData = $loadQuery->get()->keyBy('item_id');

        // 2. المبيعات (Sales) — من فواتير المبيعات
        $saleQuery = DB::table('sales_invoice_items')
            ->join('sales_invoices', 'sales_invoice_items.sales_invoice_id', '=', 'sales_invoices.id')
            ->where('sales_invoices.sales_rep_id', $employeeId)
            ->where('sales_invoices.company_id', $companyId)
            ->where('sales_invoices.status', 'posted')
            ->whereNull('sales_invoices.deleted_at')
            ->whereNull('sales_invoice_items.deleted_at')
            ->select(
                'sales_invoice_items.item_id',
                DB::raw('ABS(SUM(COALESCE(NULLIF(sales_invoice_items.base_quantity, 0), sales_invoice_items.qty))) as sale_qty'),
                DB::raw('SUM(sales_invoice_items.net_amount) as sale_amount')
            )
            ->groupBy('sales_invoice_items.item_id');

        if ($dateFrom) {
            $saleQuery->where('sales_invoices.invoice_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $saleQuery->where('sales_invoices.invoice_date', '<=', $dateTo);
        }
        $saleData = $saleQuery->get()->keyBy('item_id');

        // 3. المرتجعات (Returns) — من أوامر الإرجاع
        $returnQuery = DB::table('return_order_items')
            ->join('return_orders', 'return_order_items.return_order_id', '=', 'return_orders.id')
            ->whereNull('return_orders.deleted_at')
            ->where('return_orders.user_id', $employeeId)
            ->where('return_orders.company_id', $companyId)
            ->whereIn('return_orders.status_id', ['pending', 'approved', 'received'])
            ->select(
                'return_order_items.item_id',
                DB::raw('SUM(return_order_items.returned_quantity) as return_qty'),
                DB::raw('SUM(return_order_items.line_total) as return_amount')
            )
            ->groupBy('return_order_items.item_id');

        if ($dateFrom) {
            $returnQuery->where('return_orders.return_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $returnQuery->where('return_orders.return_date', '<=', $dateTo);
        }
        $returnData = $returnQuery->get()->keyBy('item_id');

        // 4. جمع كل أصناف مرتبطة
        $allItemIds = collect([
            $loadData->keys(),
            $saleData->keys(),
            $returnData->keys(),
        ])->flatten()->unique()->values();

        if ($allItemIds->isEmpty()) {
            return response()->json([
                'report'  => [],
                'summary' => ['load_qty' => 0, 'sale_qty' => 0, 'return_qty' => 0, 'total_items' => 0],
            ]);
        }

        // 5. جلب بيانات الأصناف
        $items = \App\Models\Item::whereIn('id', $allItemIds)
            ->with('baseUnit:id,name_ar,name_en')
            ->get()
            ->keyBy('id');

        // 6. بناء النتيجة
        $report = [];
        $totalLoad = 0;
        $totalSale = 0;
        $totalReturn = 0;

        foreach ($allItemIds as $itemId) {
            $item = $items->get($itemId);
            $loadQty = (float) ($loadData->get($itemId)?->load_qty ?? 0);
            $saleQty = (float) ($saleData->get($itemId)?->sale_qty ?? 0);
            $saleAmount = (float) ($saleData->get($itemId)?->sale_amount ?? 0);
            $returnQty = (float) ($returnData->get($itemId)?->return_qty ?? 0);
            $returnAmount = (float) ($returnData->get($itemId)?->return_amount ?? 0);

            $totalLoad += $loadQty;
            $totalSale += $saleQty;
            $totalReturn += $returnQty;

            $report[] = [
                'item_name'     => $item?->name_ar ?? $item?->name_en ?? '',
                'item_code'     => $item?->code ?? '',
                'unit_name'     => $item?->baseUnit?->name_ar ?? $item?->baseUnit?->name_en ?? '',
                'load_qty'      => $loadQty,
                'sale_qty'      => $saleQty,
                'sale_amount'   => $saleAmount,
                'return_qty'    => $returnQty,
                'return_amount' => $returnAmount,
            ];
        }

        return response()->json([
            'report' => $report,
            'summary' => [
                'load_qty'    => $totalLoad,
                'sale_qty'    => $totalSale,
                'return_qty'  => $totalReturn,
                'total_items' => count($report),
            ],
        ]);
    }

    /**
     * GET /api/reports/daily-rep-product-movement
     * تقرير حركة المنتجات اليومية per مندوب: تحميل - مبيعات - مرتجعات + مشتريات
     */
    public function dailyRepProductMovement(Request $request)
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to'   => 'required|date|after_or_equal:date_from',
            'item_id'   => 'nullable|integer|exists:items,id',
        ]);

        $companyId = $request->user()->company_id;
        $dateFrom = $request->input('date_from') . ' 00:00:00';
        $dateTo = $request->input('date_to') . ' 23:59:59';
        $itemId = $request->input('item_id');

        // ── 0. جلب كل الأصناف للـ dropdown ──
        $allItems = \App\Models\Item::whereNull('deleted_at')
            ->get(['id', 'name_ar', 'name_en', 'code'])
            ->map(fn($i) => ['id' => $i->id, 'name' => $i->name_ar ?? $i->name_en ?? '', 'code' => $i->code ?? '']);

        // ── 1. التحميل (Load) per day per rep ──
        $loadQuery = DB::table('load_request_items as lri')
            ->join('load_requests as lr', 'lri.load_request_id', '=', 'lr.id')
            ->whereNull('lr.deleted_at')
            ->where('lr.company_id', $companyId)
            ->whereIn('lr.status', ['approved', 'loading', 'completed'])
            ->whereBetween('lr.request_date', [$dateFrom, $dateTo]);
        if ($itemId) {
            $loadQuery->where('lri.item_id', $itemId);
        }
        $loadRows = $loadQuery
            ->select('lr.user_id', DB::raw('DATE(lr.request_date) as movement_date'),
                DB::raw('SUM(lri.quantity) as load_qty'))
            ->groupBy('lr.user_id', DB::raw('DATE(lr.request_date)'))
            ->get();

        // ── 2. المبيعات (Sales) per day per rep ──
        $saleQuery = DB::table('sales_invoice_items as sii')
            ->join('sales_invoices as si', 'sii.sales_invoice_id', '=', 'si.id')
            ->whereNull('si.deleted_at')
            ->whereNull('sii.deleted_at')
            ->where('si.company_id', $companyId)
            ->where('si.status', '!=', 'cancelled')
            ->whereBetween('si.invoice_date', [$dateFrom, $dateTo]);
        if ($itemId) {
            $saleQuery->where('sii.item_id', $itemId);
        }
        $saleRows = $saleQuery
            ->select('si.sales_rep_id as user_id', DB::raw('DATE(si.invoice_date) as movement_date'),
                DB::raw('ABS(SUM(COALESCE(NULLIF(sii.base_quantity, 0), sii.qty))) as sale_qty'),
                DB::raw('SUM(sii.net_amount) as sale_amount'))
            ->groupBy('si.sales_rep_id', DB::raw('DATE(si.invoice_date)'))
            ->get();

        // ── 3. المرتجعات (Returns) per day per rep ──
        $returnQuery = DB::table('return_order_items as roi')
            ->join('return_orders as ro', 'roi.return_order_id', '=', 'ro.id')
            ->whereNull('ro.deleted_at')
            ->where('ro.company_id', $companyId)
            ->whereIn('ro.status_id', ['pending', 'approved', 'received'])
            ->whereBetween('ro.return_date', [$dateFrom, $dateTo]);
        if ($itemId) {
            $returnQuery->where('roi.item_id', $itemId);
        }
        $returnRows = $returnQuery
            ->select('ro.user_id', DB::raw('DATE(ro.return_date) as movement_date'),
                DB::raw('SUM(roi.returned_quantity) as return_qty'),
                DB::raw('SUM(roi.line_total) as return_amount'))
            ->groupBy('ro.user_id', DB::raw('DATE(ro.return_date)'))
            ->get();

        // ── 4. المشتريات (Purchases) per day ──
        $purchaseTxns = \App\Models\InventoryTransaction::where('company_id', $companyId)
            ->where('status', 'posted')
            ->whereBetween('transaction_date', [$dateFrom, $dateTo])
            ->whereHas('transactionType', fn($q) => $q->where('effect', 'addition')->where('code', 'PURCHASE_RECEIPT'))
            ->with(['items' => function ($q) use ($itemId) {
                $q->select('id', 'inventory_transaction_id', 'item_id', 'qty', 'unit_cost');
                if ($itemId) $q->where('item_id', $itemId);
            }])
            ->get();

        $purchasesByDate = [];
        foreach ($purchaseTxns as $txn) {
            $date = $txn->transaction_date instanceof \Carbon\Carbon
                ? $txn->transaction_date->toDateString()
                : date('Y-m-d', strtotime($txn->transaction_date));
            $qty = 0;
            $amount = 0.0;
            foreach ($txn->items as $item) {
                $qty += abs((float) $item->qty);
                $amount += abs((float) $item->qty) * abs((float) ($item->unit_cost ?? 0));
            }
            if (!isset($purchasesByDate[$date])) {
                $purchasesByDate[$date] = ['qty' => 0, 'amount' => 0.0];
            }
            $purchasesByDate[$date]['qty'] += $qty;
            $purchasesByDate[$date]['amount'] += $amount;
        }

        // ── 5. جمع كل user_ids المندوبين ──
        $allUserIds = $loadRows->pluck('user_id')
            ->merge($saleRows->pluck('user_id'))
            ->merge($returnRows->pluck('user_id'))
            ->unique()
            ->filter()
            ->values();

        // ── 6. جلب أسماء المندوبين ──
        $employees = $allUserIds->isEmpty() ? collect()->keyBy('user_id') : DB::table('employees')
            ->whereNull('deleted_at')
            ->whereIn('user_id', $allUserIds)
            ->select(
                'user_id',
                DB::raw("TRIM(COALESCE(first_name_ar, '') || ' ' || COALESCE(second_name_ar, '') || ' ' || COALESCE(third_name_ar, '') || ' ' || COALESCE(last_name_ar, '')) as rep_name")
            )
            ->get()
            ->keyBy('user_id');

        // ── 7. بناء بيانات pivot ──
        $pivot = [];
        foreach ($loadRows as $row) {
            $date = $row->movement_date;
            $uid = $row->user_id;
            $pivot[$date][$uid]['load_qty'] = (float) $row->load_qty;
        }
        foreach ($saleRows as $row) {
            $date = $row->movement_date;
            $uid = $row->user_id;
            $pivot[$date][$uid]['sale_qty'] = (float) $row->sale_qty;
            $pivot[$date][$uid]['sale_amount'] = (float) $row->sale_amount;
        }
        foreach ($returnRows as $row) {
            $date = $row->movement_date;
            $uid = $row->user_id;
            $pivot[$date][$uid]['return_qty'] = (float) $row->return_qty;
            $pivot[$date][$uid]['return_amount'] = (float) $row->return_amount;
        }

        // ── 8. جميع الأيام ──
        $allDays = [];
        $current = \Carbon\Carbon::parse($request->input('date_from'));
        $end = \Carbon\Carbon::parse($request->input('date_to'));
        while ($current->lte($end)) {
            $allDays[] = $current->toDateString();
            $current->addDay();
        }

        // ── 9. بناء الاستجابة ──
        $result = [];
        $totalLoad = 0;
        $totalSale = 0;
        $totalSaleAmount = 0;
        $totalReturn = 0;
        $totalReturnAmount = 0;
        $totalPurchaseQty = 0;
        $totalPurchaseAmount = 0;

        foreach ($allDays as $date) {
            $dayReps = [];
            $dayLoad = 0;
            $daySale = 0;
            $daySaleAmount = 0;
            $dayReturn = 0;
            $dayReturnAmount = 0;

            $repsForDay = isset($pivot[$date]) ? array_keys($pivot[$date]) : [];
            foreach ($repsForDay as $uid) {
                $data = $pivot[$date][$uid];
                $rep = $employees[$uid] ?? null;
                $lq = $data['load_qty'] ?? 0;
                $sq = $data['sale_qty'] ?? 0;
                $sa = $data['sale_amount'] ?? 0;
                $rq = $data['return_qty'] ?? 0;
                $ra = $data['return_amount'] ?? 0;

                $dayLoad += $lq;
                $daySale += $sq;
                $daySaleAmount += $sa;
                $dayReturn += $rq;
                $dayReturnAmount += $ra;

                $dayReps[] = [
                    'user_id'      => $uid,
                    'rep_name'     => $rep?->rep_name ?? "مندوب #$uid",
                    'load_qty'     => $lq,
                    'sale_qty'     => $sq,
                    'sale_amount'  => round($sa, 2),
                    'return_qty'   => $rq,
                    'return_amount'=> round($ra, 2),
                ];
            }

            $purchaseData = $purchasesByDate[$date] ?? ['qty' => 0, 'amount' => 0];

            $totalLoad += $dayLoad;
            $totalSale += $daySale;
            $totalSaleAmount += $daySaleAmount;
            $totalReturn += $dayReturn;
            $totalReturnAmount += $dayReturnAmount;
            $totalPurchaseQty += $purchaseData['qty'];
            $totalPurchaseAmount += $purchaseData['amount'];

            $result[] = [
                'date'            => $date,
                'reps'            => $dayReps,
                'purchases_qty'   => $purchaseData['qty'],
                'purchases_amount'=> round($purchaseData['amount'], 2),
                'day_total_load'  => $dayLoad,
                'day_total_sales' => $daySale,
                'day_total_sales_amount' => round($daySaleAmount, 2),
                'day_total_returns'=> $dayReturn,
                'day_total_returns_amount' => round($dayReturnAmount, 2),
            ];
        }

        return response()->json([
            'data' => [
                'items'   => $allItems,
                'item_id' => $itemId,
                'days' => $result,
                'summary' => [
                    'total_load'           => $totalLoad,
                    'total_sales'          => $totalSale,
                    'total_sales_amount'   => round($totalSaleAmount, 2),
                    'total_returns'        => $totalReturn,
                    'total_returns_amount' => round($totalReturnAmount, 2),
                    'total_purchases_qty'  => $totalPurchaseQty,
                    'total_purchases_amount'=> round($totalPurchaseAmount, 2),
                ],
            ],
        ]);
    }

    /**
     * GET /api/reports/sales-by-rep
     * تقرير مبيعات المندوبين - أعمدة = المنتجات، صفوف = المندوبين
     */
    public function salesByRep(Request $request)
    {
        $request->validate([
            'date'    => 'required|date',
            'rep_id'  => 'nullable|integer',
        ]);

        $companyId = $request->user()->company_id;
        $date = $request->input('date');
        $repId = $request->input('rep_id');

        // 1. جلب المبيعات: quantity + amount per rep per item
        $saleQuery = DB::table('sales_invoice_items as sii')
            ->join('sales_invoices as si', 'sii.sales_invoice_id', '=', 'si.id')
            ->whereNull('si.deleted_at')
            ->whereNull('sii.deleted_at')
            ->where('si.company_id', $companyId)
            ->where('si.status', '!=', 'cancelled')
            ->whereDate('si.invoice_date', $date)
            ->select(
                'si.sales_rep_id as user_id',
                'sii.item_id',
                DB::raw('ABS(SUM(COALESCE(NULLIF(sii.base_quantity, 0), sii.qty))) as total_qty'),
                DB::raw('SUM(sii.net_amount) as total_amount')
            )
            ->groupBy('si.sales_rep_id', 'sii.item_id');

        if ($repId) {
            $saleQuery->where('si.sales_rep_id', $repId);
        }

        $saleRows = $saleQuery->get();

        // 2. المرتجعات per rep per item
        $returnQuery = DB::table('return_order_items as roi')
            ->join('return_orders as ro', 'roi.return_order_id', '=', 'ro.id')
            ->whereNull('ro.deleted_at')
            ->where('ro.company_id', $companyId)
            ->whereIn('ro.status_id', ['pending', 'approved', 'received'])
            ->whereDate('ro.return_date', $date)
            ->select(
                'ro.user_id',
                'roi.item_id',
                DB::raw('SUM(roi.returned_quantity) as return_qty'),
                DB::raw('SUM(roi.line_total) as return_amount')
            )
            ->groupBy('ro.user_id', 'roi.item_id');

        if ($repId) {
            $returnQuery->where('ro.user_id', $repId);
        }

        $returnRows = $returnQuery->get();

        // 3. جمع كل الأصناف والمندوبين
        $allItemIds = $saleRows->pluck('item_id')
            ->merge($returnRows->pluck('item_id'))
            ->unique()->filter()->values();

        $allUserIds = $saleRows->pluck('user_id')
            ->merge($returnRows->pluck('user_id'))
            ->unique()->filter()->values();

        // 4. جلب بيانات الأصناف
        $items = $allItemIds->isEmpty() ? collect() : \App\Models\Item::whereIn('id', $allItemIds)
            ->with('baseUnit:id,name_ar,name_en')
            ->get(['id', 'name_ar', 'name_en', 'code'])
            ->keyBy('id');

        // 5. جلب أسماء المندوبين
        $employees = $allUserIds->isEmpty() ? collect()->keyBy('user_id') : DB::table('employees')
            ->whereNull('deleted_at')
            ->whereIn('user_id', $allUserIds)
            ->select(
                'user_id',
                DB::raw("TRIM(COALESCE(first_name_ar, '') || ' ' || COALESCE(second_name_ar, '') || ' ' || COALESCE(third_name_ar, '') || ' ' || COALESCE(last_name_ar, '')) as rep_name")
            )
            ->get()
            ->keyBy('user_id');

        // 6. بناء pivot: rep -> item -> {qty, amount, return_qty, return_amount}
        $pivot = [];
        foreach ($saleRows as $row) {
            $uid = $row->user_id;
            $iid = $row->item_id;
            $pivot[$uid][$iid]['qty'] = (float) $row->total_qty;
            $pivot[$uid][$iid]['amount'] = (float) $row->total_amount;
        }
        foreach ($returnRows as $row) {
            $uid = $row->user_id;
            $iid = $row->item_id;
            if (!isset($pivot[$uid][$iid])) $pivot[$uid][$iid] = ['qty' => 0, 'amount' => 0];
            $pivot[$uid][$iid]['return_qty'] = (float) $row->return_qty;
            $pivot[$uid][$iid]['return_amount'] = (float) $row->return_amount;
        }

        // 7. بناء الاستجابة
        $reps = [];
        foreach ($allUserIds as $uid) {
            $emp = $employees[$uid] ?? null;
            $itemsData = [];
            $totalQty = 0;
            $totalAmount = 0;
            $totalReturnQty = 0;
            $totalReturnAmount = 0;

            foreach ($allItemIds as $iid) {
                $d = $pivot[$uid][$iid] ?? null;
                $q = $d['qty'] ?? 0;
                $a = $d['amount'] ?? 0;
                $rq = $d['return_qty'] ?? 0;
                $ra = $d['return_amount'] ?? 0;
                $totalQty += $q;
                $totalAmount += $a;
                $totalReturnQty += $rq;
                $totalReturnAmount += $ra;
                $itemsData[$iid] = [
                    'qty'           => round($q, 2),
                    'amount'        => round($a, 2),
                    'return_qty'    => round($rq, 2),
                    'return_amount' => round($ra, 2),
                ];
            }

            $reps[] = [
                'user_id'            => $uid,
                'rep_name'           => $emp?->rep_name ?? "مندوب #$uid",
                'items'              => $itemsData,
                'total_qty'          => round($totalQty, 2),
                'total_amount'       => round($totalAmount, 2),
                'total_return_qty'   => round($totalReturnQty, 2),
                'total_return_amount'=> round($totalReturnAmount, 2),
            ];
        }

        // 8. إجمالي كل صنف
        $itemTotals = [];
        foreach ($allItemIds as $iid) {
            $tq = 0; $ta = 0; $trq = 0; $tra = 0;
            foreach ($reps as $rep) {
                $d = $rep['items'][$iid] ?? null;
                if ($d) {
                    $tq += $d['qty'];
                    $ta += $d['amount'];
                    $trq += $d['return_qty'];
                    $tra += $d['return_amount'];
                }
            }
            $item = $items->get($iid);
            $itemTotals[$iid] = [
                'item_name'    => $item?->name_ar ?? $item?->name_en ?? '',
                'item_code'    => $item?->code ?? '',
                'unit_name'    => $item?->baseUnit?->name_ar ?? $item?->baseUnit?->name_en ?? '',
                'total_qty'    => round($tq, 2),
                'total_amount' => round($ta, 2),
                'return_qty'   => round($trq, 2),
                'return_amount'=> round($tra, 2),
            ];
        }

        return response()->json([
            'data' => [
                'date'       => $date,
                'items'      => array_values($itemTotals),
                'reps'       => $reps,
                'item_ids'   => $allItemIds->values()->all(),
            ],
        ]);
    }

    /**
     * GET /api/reports/customer-invoice-payments
     * تقرير تفاصيل فواتير العملاء: قيمة الفاتورة، المدفوع، المتبقي
     */
    public function customerInvoicePayments(Request $request)
    {
        $request->validate([
            'date_from'    => 'required|date',
            'date_to'      => 'required|date|after_or_equal:date_from',
            'customer_id'  => 'nullable|integer',
            'sales_rep_id' => 'nullable|integer',
        ]);

        $companyId = $request->user()->company_id;
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $customerId = $request->input('customer_id');
        $salesRepId = $request->input('sales_rep_id');

        $invoices = DB::table('sales_invoices')
            ->join('customers', 'sales_invoices.customer_id', '=', 'customers.id')
            ->whereNull('customers.deleted_at')
            ->leftJoin('employees', 'sales_invoices.sales_rep_id', '=', 'employees.user_id')
            ->whereNull('employees.deleted_at')
            ->where('sales_invoices.company_id', $companyId)
            ->whereDate('sales_invoices.invoice_date', '>=', $dateFrom)
            ->whereDate('sales_invoices.invoice_date', '<=', $dateTo)
            ->where('sales_invoices.status', 'posted')
            ->whereNull('sales_invoices.deleted_at')
            ->select(
                'sales_invoices.id',
                'sales_invoices.invoice_no',
                'sales_invoices.invoice_date',
                'customers.code as customer_code',
                'customers.name_ar as customer_name',
                DB::raw("COALESCE(employees.first_name_ar, '') || ' ' || COALESCE(employees.last_name_ar, '') as sales_rep_name"),
                'sales_invoices.net_total',
                'sales_invoices.paid_amount',
                DB::raw('(sales_invoices.net_total - sales_invoices.paid_amount) as remaining_amount')
            );

        if ($customerId) {
            $invoices->where('sales_invoices.customer_id', $customerId);
        }
        if ($salesRepId) {
            $invoices->where('sales_invoices.sales_rep_id', $salesRepId);
        }

        $invoices = $invoices->orderBy('sales_invoices.invoice_date')
            ->orderBy('sales_invoices.invoice_no')
            ->get();

        $totalNet = $invoices->sum('net_total');
        $totalPaid = $invoices->sum('paid_amount');
        $totalRemaining = $invoices->sum('remaining_amount');

        return response()->json([
            'data' => [
                'invoices' => $invoices,
                'summary' => [
                    'total_invoices' => $invoices->count(),
                    'total_net' => round($totalNet, 2),
                    'total_paid' => round($totalPaid, 2),
                    'total_remaining' => round($totalRemaining, 2),
                ],
            ],
        ]);
    }

    /**
     * GET /api/reports/inactive-customers
     * تقرير العملاء غير الفعّالة (بدون مشتريات) خلال فترة
     * فلاتر: تاريخ (من - إلى)، المنطقة (area_id)، خط السير (route_id)
     */
    public function inactiveCustomers(Request $request)
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to'   => 'required|date|after_or_equal:date_from',
            'area_id'   => 'nullable|integer',
            'route_id'  => 'nullable|integer',
        ]);

        $companyId = $request->user()->company_id;
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $areaId = $request->input('area_id');
        $routeId = $request->input('route_id');

        $base = DB::table('customers')
            ->whereNull('customers.deleted_at')
            ->where('customers.company_id', $companyId);

        if ($areaId) {
            $base->where('customers.area_id', $areaId);
        }
        if ($routeId) {
            $base->whereExists(function ($q) use ($routeId) {
                $q->select(DB::raw(1))
                    ->from('route_customers')
                    ->whereColumn('route_customers.customer_id', 'customers.id')
                    ->where('route_customers.route_id', $routeId)
                    ->whereNull('route_customers.deleted_at');
            });
        }

        $totalCustomers = (clone $base)->count();

        // عملاء بدون أي فواتير شراء داخل الفترة المحددة
        $rows = (clone $base)
            ->whereNotExists(function ($q) use ($companyId, $dateFrom, $dateTo) {
                $q->select(DB::raw(1))
                    ->from('sales_invoices')
                    ->whereColumn('sales_invoices.customer_id', 'customers.id')
                    ->where('sales_invoices.company_id', $companyId)
                    ->whereNull('sales_invoices.deleted_at')
                    ->where('sales_invoices.status', '!=', 'cancelled')
                    ->whereDate('sales_invoices.invoice_date', '>=', $dateFrom)
                    ->whereDate('sales_invoices.invoice_date', '<=', $dateTo);
            })
            ->leftJoin('districts', 'districts.id', '=', 'customers.area_id')
            ->select(
                'customers.id as customer_id',
                'customers.code as customer_code',
                'customers.name_ar as customer_name',
                'customers.phone',
                'customers.mobile',
                'customers.is_active',
                'customers.created_at',
                DB::raw("COALESCE(districts.name, '') as area_name"),
            )
            ->orderBy('customers.name_ar')
            ->get();

        $customerIds = $rows->pluck('customer_id');

        $lastPurchases = $customerIds->isEmpty()
            ? collect()
            : DB::table('sales_invoices')
                ->where('company_id', $companyId)
                ->whereNull('deleted_at')
                ->where('status', '!=', 'cancelled')
                ->whereDate('invoice_date', '<', $dateFrom)
                ->whereIn('customer_id', $customerIds)
                ->groupBy('customer_id')
                ->select(
                    'customer_id',
                    DB::raw('MAX(invoice_date) as last_date'),
                    DB::raw('SUM(net_total) as total_before')
                )
                ->get()
                ->keyBy('customer_id');

        $visits = $customerIds->isEmpty()
            ? collect()
            : DB::table('customer_visits')
                ->whereNull('deleted_at')
                ->whereDate('visit_date', '>=', $dateFrom)
                ->whereDate('visit_date', '<=', $dateTo)
                ->whereIn('customer_id', $customerIds)
                ->groupBy('customer_id')
                ->select('customer_id', DB::raw('COUNT(*) as visits_count'))
                ->get()
                ->keyBy('customer_id');

        $routeNames = $customerIds->isEmpty()
            ? collect()
            : DB::table('route_customers')
                ->join('routes', 'routes.id', '=', 'route_customers.route_id')
                ->whereNull('route_customers.deleted_at')
                ->whereNull('routes.deleted_at')
                ->whereIn('route_customers.customer_id', $customerIds)
                ->orderBy('route_customers.visit_order')
                ->get(['route_customers.customer_id', 'routes.name_ar as route_name'])
                ->groupBy('customer_id')
                ->map(fn($g) => $g->pluck('route_name')->unique()->filter()->implode('، '));

        $customers = $rows->map(function ($c) use ($lastPurchases, $visits, $routeNames) {
            $last = $lastPurchases[$c->customer_id] ?? null;

            return [
                'customer_id' => (int) $c->customer_id,
                'customer_code' => (string) $c->customer_code,
                'customer_name' => (string) $c->customer_name,
                'phone' => (string) ($c->phone ?: ''),
                'mobile' => (string) ($c->mobile ?: ''),
                'is_active' => (bool) $c->is_active,
                'area_name' => (string) $c->area_name,
                'route_name' => (string) ($routeNames[$c->customer_id] ?? ''),
                'last_purchase_date' => $last?->last_date,
                'total_before_period' => round((float) ($last->total_before ?? 0), 2),
                'visits_in_period' => (int) ($visits[$c->customer_id]->visits_count ?? 0),
            ];
        })->values();

        $neverPurchased = $customers->whereNull('last_purchase_date')->count();

        return response()->json([
            'data' => [
                'customers' => $customers,
                'summary' => [
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'total_customers' => $totalCustomers,
                    'total_inactive' => $customers->count(),
                    'never_purchased' => $neverPurchased,
                    'inactive_percentage' => $totalCustomers > 0
                        ? round(($customers->count() / $totalCustomers) * 100, 1)
                        : 0,
                ],
            ],
        ]);
    }

    /**
     * item_id => conversion factor of the requested unit for that item.
     * The sales line quantity is stored in the base unit (box), so
     * qty in target unit = (qty * line conversion_factor) / unit factor.
     * When $unitId is null the carton unit (units named carton) is used.
     * Items without the requested unit fall back to factor 1.
     */
    private function unitFactorMap(array $itemIds, ?int $unitId): array
    {
        if (empty($itemIds) || $unitId === null) {
            return [];
        }

        $rows = DB::table('item_units as iu')
            ->join('units', 'units.id', '=', 'iu.unit_id')
            ->whereNull('iu.deleted_at')
            ->whereNull('units.deleted_at')
            ->whereIn('iu.item_id', $itemIds)
            ->where('iu.unit_id', $unitId)
            ->get(['iu.item_id', 'iu.conversion_factor']);

        $map = [];
        foreach ($rows as $row) {
            $factor = (float) $row->conversion_factor;
            if ($factor <= 0) {
                $factor = 1.0;
            }
            $itemId = (int) $row->item_id;
            $map[$itemId] = max($map[$itemId] ?? 1.0, $factor);
        }

        return $map;
    }

    /**
     * GET /api/reports/customer-sales-qty
     * تقرير مبيعات العملاء بالكمية خلال فترة مع فلتر مرن على الكمية:
     *   - unit_id: وحدة عرض الكميات (افتراضي الكرتونة) — تُقرأ معاملات التحويل من item_units
     *   - qty_min / qty_max: الكمية ضمن النطاق (شامل الطرفين)
     *   - qty_gt: أكثر من كمية   |   qty_lt: أقل من كمية
     * أمثلة: أكثر من5 كراتين → qty_gt=5 | أقل من5 → qty_lt=5 | من1 إلى5 → qty_min=1&qty_max=5
     */
    public function customerSalesQty(Request $request)
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'customer_id' => 'nullable|integer',
            'territory_id' => 'nullable|integer',
            'route_id' => 'nullable|integer',
            'unit_id' => 'nullable|integer|exists:units,id',
            'qty_min' => 'nullable|numeric|min:0',
            'qty_max' => 'nullable|numeric|min:0',
            'qty_gt' => 'nullable|numeric|min:0',
            'qty_lt' => 'nullable|numeric|min:0',
        ]);

        $qtyMin = $request->filled('qty_min') ? (float) $request->input('qty_min') : null;
        $qtyMax = $request->filled('qty_max') ? (float) $request->input('qty_max') : null;
        $qtyGt = $request->filled('qty_gt') ? (float) $request->input('qty_gt') : null;
        $qtyLt = $request->filled('qty_lt') ? (float) $request->input('qty_lt') : null;

        // وحدة العرض: الوحدة المختارة، أو الكرتونة افتراضياً
        $unitId = $request->filled('unit_id') ? (int) $request->input('unit_id') : null;
        $unitName = '';
        if ($unitId !== null) {
            $unitName = (string) (DB::table('units')->where('id', $unitId)->value('name_ar') ?? '');
        } else {
            $cartonId = DB::table('units')
                ->whereNull('deleted_at')
                ->where(function ($q) {
                    $q->where('name_ar', 'like', '%كرتون%')
                        ->orWhere('name_en', 'like', '%carton%');
                })
                ->orderBy('id')
                ->value('id');
            $unitId = $cartonId !== null ? (int) $cartonId : null;
            $unitName = $unitId !== null
                ? (string) (DB::table('units')->where('id', $unitId)->value('name_ar') ?? '')
                : '';
        }

        if ($qtyMin !== null && $qtyMax !== null && $qtyMax < $qtyMin) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'qty_max' => 'الحد الأعلى للكمية يجب أن يكون أكبر من أو يساوي الحد الأدنى',
            ]);
        }

        $companyId = $request->user()->company_id;
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $customerId = $request->input('customer_id');
        $territoryId = $request->input('territory_id');
        $routeId = $request->input('route_id');

        // أساس الفواتير داخل الفترة (يُستدعى مرتين: للقيم وللكميات)
        $invoiceBase = fn () => DB::table('sales_invoices as si')
            ->where('si.company_id', $companyId)
            ->whereDate('si.invoice_date', '>=', $dateFrom)
            ->whereDate('si.invoice_date', '<=', $dateTo)
            ->where('si.status', 'posted')
            ->whereNull('si.deleted_at')
            ->when($customerId, fn ($q) => $q->where('si.customer_id', $customerId))
            ->when($territoryId, fn ($q) => $q->where('si.sales_territory_id', $territoryId))
            ->when($routeId, fn ($q) => $q->where('si.route_id', $routeId));

        // 1) عدد الفواتير والقيمة لكل عميل
        $sales = $invoiceBase()
            ->join('customers', 'customers.id', '=', 'si.customer_id')
            ->whereNull('customers.deleted_at')
            ->groupBy('si.customer_id', 'customers.code', 'customers.name_ar')
            ->select(
                'si.customer_id',
                'customers.code as customer_code',
                'customers.name_ar as customer_name',
                DB::raw('COUNT(*) as invoices_count'),
                DB::raw('SUM(si.net_total) as sales_total'),
                DB::raw('SUM(si.paid_amount) as paid_total')
            )
            ->get();

        // 2) الكميات (كراتين) وعدد الأصناف لكل عميل
        $lines = $invoiceBase()
            ->join('sales_invoice_items as sii', 'sii.sales_invoice_id', '=', 'si.id')
            ->whereNull('sii.deleted_at')
            ->groupBy('si.customer_id', 'sii.item_id')
            ->select(
                'si.customer_id',
                'sii.item_id',
                DB::raw('SUM(COALESCE(sii.qty, 0) * COALESCE(NULLIF(sii.conversion_factor, 0), 1)) as base_qty'),
                DB::raw('SUM(COALESCE(sii.bonus_qty, 0) * COALESCE(NULLIF(sii.conversion_factor, 0), 1)) as bonus_base')
            )
            ->get();

        $itemIds = $lines->pluck('item_id')->unique()->values()->all();
        $factors = $this->unitFactorMap($itemIds, $unitId);

        // وحدات القياس المتاحة لأصناف التقرير (لعرضها في اختيار الوحدة)
        $units = DB::table('item_units as iu')
            ->join('units as u', 'u.id', '=', 'iu.unit_id')
            ->whereNull('iu.deleted_at')
            ->whereNull('u.deleted_at')
            ->whereIn('iu.item_id', $itemIds)
            ->groupBy('u.id', 'u.name_ar')
            ->select('u.id', 'u.name_ar', DB::raw('MAX(iu.conversion_factor) as conversion_factor'))
            ->orderByDesc('conversion_factor')
            ->get();

        if ($units->isEmpty()) {
            $units = DB::table('units')
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->get(['id', 'name_ar']);
        }

        $qties = $lines
            ->groupBy('customer_id')
            ->map(function ($rows) use ($factors) {
                $qty = 0.0;
                $bonus = 0.0;

                foreach ($rows as $row) {
                    $factor = $factors[(int) $row->item_id] ?? 1.0;
                    if ($factor <= 0) {
                        $factor = 1.0;
                    }

                    $qty += (float) $row->base_qty / $factor;
                    $bonus += (float) $row->bonus_base / $factor;
                }

                return (object) [
                    'qty_total' => $qty,
                    'bonus_total' => $bonus,
                    'items_count' => $rows->count(),
                ];
            });

        // دمج + فلتر مرن على الكمية
        $customers = $sales
            ->map(function ($row) use ($qties, $qtyMin, $qtyMax, $qtyGt, $qtyLt) {
                $qty = (float) ($qties[$row->customer_id]->qty_total ?? 0);

                return [
                    'customer_id' => (int) $row->customer_id,
                    'customer_code' => (string) $row->customer_code,
                    'customer_name' => (string) $row->customer_name,
                    'invoices_count' => (int) $row->invoices_count,
                    'qty_total' => round($qty, 2),
                    'bonus_total' => round((float) ($qties[$row->customer_id]->bonus_total ?? 0), 2),
                    'items_count' => (int) ($qties[$row->customer_id]->items_count ?? 0),
                    'sales_total' => round((float) $row->sales_total, 2),
                    'paid_total' => round((float) $row->paid_total, 2),
                    'remaining_total' => round((float) $row->sales_total - (float) $row->paid_total, 2),
                    'route_name' => '',
                    'territory_name' => '',
                    '_qty' => $qty,
                ];
            })
            ->filter(function ($c) use ($qtyMin, $qtyMax, $qtyGt, $qtyLt) {
                $q = $c['_qty'];
                if ($qtyMin !== null && $q < $qtyMin) {
                    return false;
                }
                if ($qtyMax !== null && $q > $qtyMax) {
                    return false;
                }
                if ($qtyGt !== null && $q <= $qtyGt) {
                    return false;
                }
                if ($qtyLt !== null && $q >= $qtyLt) {
                    return false;
                }

                return true;
            })
            ->sortByDesc('qty_total')
            ->values();

        // أسماء خطوط السير والمناطق للعملاء المطلوبين فقط
        $customerIds = $customers->pluck('customer_id');
        $routeInfo = $customerIds->isEmpty()
            ? collect()
            : DB::table('route_customers')
                ->join('routes', 'routes.id', '=', 'route_customers.route_id')
                ->leftJoin('sales_territories', 'sales_territories.id', '=', 'routes.sales_territory_id')
                ->whereNull('route_customers.deleted_at')
                ->whereNull('routes.deleted_at')
                ->whereIn('route_customers.customer_id', $customerIds)
                ->orderBy('route_customers.visit_order')
                ->get([
                    'route_customers.customer_id',
                    'routes.name_ar as route_name',
                    'sales_territories.name_ar as territory_name',
                ])
                ->groupBy('customer_id')
                ->map(fn ($g) => [
                    'route_name' => $g->pluck('route_name')->unique()->filter()->implode('، '),
                    'territory_name' => $g->pluck('territory_name')->unique()->filter()->implode('، '),
                ]);

        $customers = $customers->map(function ($c) use ($routeInfo) {
            unset($c['_qty']);
            $info = $routeInfo[$c['customer_id']] ?? null;
            $c['route_name'] = (string) ($info['route_name'] ?? '');
            $c['territory_name'] = (string) ($info['territory_name'] ?? '');

            return $c;
        });

        return response()->json([
            'data' => [
                'customers' => $customers,
                'units' => $units->values(),
                'summary' => [
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'unit_id' => $unitId,
                    'qty_unit' => $unitName,
                    'qty_min' => $qtyMin,
                    'qty_max' => $qtyMax,
                    'qty_gt' => $qtyGt,
                    'qty_lt' => $qtyLt,
                    'customers_count' => $customers->count(),
                    'total_qty' => round($customers->sum('qty_total'), 2),
                    'total_bonus' => round($customers->sum('bonus_total'), 2),
                    'total_invoices' => (int) $customers->sum('invoices_count'),
                    'total_sales' => round($customers->sum('sales_total'), 2),
                    'total_remaining' => round($customers->sum('remaining_total'), 2),
                    'avg_qty' => $customers->count() > 0
                        ? round($customers->sum('qty_total') / $customers->count(), 2)
                        : 0,
                ],
            ],
        ]);
    }

    /**
     * GET /api/reports/customer-sales-qty-detail
     * داتا جرد باليوم لعميل واحد: صفوف = الأيام، أعمدة = الأصناف + الإجمالي
     * الكميات محوّلة لوحدة العرض (unit_id، افتراضي الكرتونة).
     */
    public function customerSalesQtyDetail(Request $request)
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'customer_id' => 'required|integer',
            'unit_id' => 'nullable|integer|exists:units,id',
        ]);

        $companyId = $request->user()->company_id;
        $customerId = (int) $request->input('customer_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $unitId = $request->filled('unit_id') ? (int) $request->input('unit_id') : null;
        $unitName = '';
        if ($unitId !== null) {
            $unitName = (string) (DB::table('units')->where('id', $unitId)->value('name_ar') ?? '');
        } else {
            $cartonId = DB::table('units')
                ->whereNull('deleted_at')
                ->where(function ($q) {
                    $q->where('name_ar', 'like', '%كرتون%')
                        ->orWhere('name_en', 'like', '%carton%');
                })
                ->orderBy('id')
                ->value('id');
            $unitId = $cartonId !== null ? (int) $cartonId : null;
            $unitName = $unitId !== null
                ? (string) (DB::table('units')->where('id', $unitId)->value('name_ar') ?? '')
                : '';
        }

        $customer = DB::table('customers')
            ->whereNull('deleted_at')
            ->where('id', $customerId)
            ->first(['id', 'code', 'name_ar']);

        // الكميات الأساسية (بالعلبة) لكل يوم ولكل صنف
        $rows = DB::table('sales_invoices as si')
            ->join('sales_invoice_items as sii', 'sii.sales_invoice_id', '=', 'si.id')
            ->join('items', 'items.id', '=', 'sii.item_id')
            ->where('si.company_id', $companyId)
            ->whereDate('si.invoice_date', '>=', $dateFrom)
            ->whereDate('si.invoice_date', '<=', $dateTo)
            ->where('si.status', 'posted')
            ->whereNull('si.deleted_at')
            ->whereNull('sii.deleted_at')
            ->whereNull('items.deleted_at')
            ->where('si.customer_id', $customerId)
            ->groupBy(DB::raw('DATE(si.invoice_date)'), 'sii.item_id', 'items.name_ar')
            ->selectRaw(
                'DATE(si.invoice_date) as sale_date,
                 sii.item_id,
                 items.name_ar as item_name,
                 SUM(COALESCE(sii.qty, 0) * COALESCE(NULLIF(sii.conversion_factor, 0), 1)) as base_qty'
            )
            ->get();

        $itemIds = $rows->pluck('item_id')->unique()->values()->all();
        $factors = $this->unitFactorMap($itemIds, $unitId);

        $matrix = [];       // date => item_id => qty
        $itemTotals = [];   // item_id => qty
        foreach ($rows as $row) {
            $factor = $factors[(int) $row->item_id] ?? 1.0;
            if ($factor <= 0) {
                $factor = 1.0;
            }

            $qty = (float) $row->base_qty / $factor;
            $date = (string) $row->sale_date;
            $itemId = (int) $row->item_id;

            $matrix[$date][$itemId] = ($matrix[$date][$itemId] ?? 0.0) + $qty;
            $itemTotals[$itemId] = ($itemTotals[$itemId] ?? 0.0) + $qty;
        }

        // ترتيب الأصناف: الأكثر كمية ثم الاسم
        $itemNames = $rows->pluck('item_name', 'item_id');
        $products = collect($itemTotals)
            ->sortByDesc(fn ($qty, $id) => $qty)
            ->keys()
            ->map(fn ($id) => [
                'item_id' => (int) $id,
                'item_name' => (string) ($itemNames[$id] ?? ''),
            ])
            ->values();

        $dates = array_keys($matrix);
        sort($dates);

        $rowsOut = [];
        foreach ($dates as $date) {
            $cells = [];
            $total = 0.0;
            foreach ($products as $product) {
                $v = $matrix[$date][$product['item_id']] ?? 0.0;
                $cells[] = round($v, 2);
                $total += $v;
            }
            $rowsOut[] = [
                'date' => $date,
                'cells' => $cells,
                'total' => round($total, 2),
            ];
        }

        $totalsCells = [];
        $grandTotal = 0.0;
        foreach ($products as $product) {
            $v = $itemTotals[$product['item_id']] ?? 0.0;
            $totalsCells[] = round($v, 2);
            $grandTotal += $v;
        }

        return response()->json([
            'data' => [
                'customer' => [
                    'customer_id' => $customerId,
                    'customer_code' => (string) ($customer->code ?? ''),
                    'customer_name' => (string) ($customer->name_ar ?? ''),
                ],
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'unit_id' => $unitId,
                'unit_name' => $unitName,
                'products' => $products,
                'rows' => $rowsOut,
                'totals' => [
                    'cells' => $totalsCells,
                    'total' => round($grandTotal, 2),
                ],
            ],
        ]);
    }

    /**
     * تفاصيل بطاقتي المدينون / الدائنون في الداش بورد.
     * GET /api/reports/balances/{type}   type = debtors | creditors
     * كل سطر: الاسم - مبيعات/مشتريات اليوم - المدين - الدائن - الإجمالي (الرصيد)
     * الإجمالي مطابق تماماً لحساب بطاقة الداش بورد.
     */
    public function counterpartyBalances(Request $request, string $type)
    {
        if (!in_array($type, ['debtors', 'creditors'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'نوع التقرير غير صحيح',
            ], 422);
        }

        $user = $request->user();
        $companyId = (int) ($request->header('X-Company-Id') ?? $user?->company_id);
        $today = Carbon::today()->toDateString();

        $isDebtors = $type === 'debtors';
        $docTable = $isDebtors ? 'sales_invoices' : 'purchase_invoices';
        $partyTable = $isDebtors ? 'customers' : 'suppliers';
        $partyColumn = $isDebtors ? 'customer_id' : 'supplier_id';
        $nameColumn = $isDebtors ? 'name_ar' : 'supplier_name';
        $codeColumn = $isDebtors ? 'code' : 'supplier_code';

        $balanceExpr = 'CASE
                WHEN COALESCE(d.remaining_amount, 0) > (COALESCE(d.net_total, 0) - COALESCE(d.paid_amount, 0))
                    THEN COALESCE(d.remaining_amount, 0)
                ELSE (COALESCE(d.net_total, 0) - COALESCE(d.paid_amount, 0))
            END';

        $rows = DB::table("{$docTable} as d")
            ->leftJoin("{$partyTable} as p", 'p.id', '=', "d.{$partyColumn}")
            ->where('d.company_id', $companyId)
            ->where('d.status', '!=', 'cancelled')
            ->whereNull('d.deleted_at')
            ->groupBy('p.id', "p.{$nameColumn}", "p.{$codeColumn}")
            ->selectRaw("p.id as party_id, p.{$nameColumn} as party_name, p.{$codeColumn} as party_code")
            ->selectRaw('COALESCE(SUM(COALESCE(d.net_total, 0)), 0) as debit_total')
            ->selectRaw('COALESCE(SUM(COALESCE(d.paid_amount, 0)), 0) as credit_total')
            ->selectRaw("COALESCE(SUM(CASE WHEN DATE(d.invoice_date) = '{$today}' THEN COALESCE(d.net_total, 0) ELSE 0 END), 0) as today_total")
            ->selectRaw("COALESCE(SUM({$balanceExpr}), 0) as balance")
            ->havingRaw('balance > 0')
            ->orderByDesc('balance')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->party_id,
                'code' => (string) ($row->party_code ?? ''),
                'name' => (string) ($row->party_name ?? 'غير محدد'),
                'today' => round((float) $row->today_total, 2),
                'debit' => round((float) $row->debit_total, 2),
                'credit' => round((float) $row->credit_total, 2),
                'total' => round((float) $row->balance, 2),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $today,
                'rows' => $rows,
                'meta' => [
                    'type' => $type,
                    'label' => $isDebtors ? 'المدينون' : 'الدائنون',
                    'name_header' => $isDebtors ? 'العميل' : 'المورد',
                    'today_header' => $isDebtors ? 'مبيعات اليوم' : 'مشتريات اليوم',
                ],
                'summary' => [
                    'count' => $rows->count(),
                    'today' => round((float) $rows->sum('today'), 2),
                    'debit' => round((float) $rows->sum('debit'), 2),
                    'credit' => round((float) $rows->sum('credit'), 2),
                    'total' => round((float) $rows->sum('total'), 2),
                ],
            ],
        ]);
    }
}
