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

        $customersQuery = DB::table('customers')
            ->whereNull('customers.deleted_at')
            ->leftJoin('employees', function ($q) use ($companyId, $dateFrom, $dateTo) {
                $q->on('employees.user_id', '=', DB::raw('(SELECT sales_invoices.sales_rep_id FROM sales_invoices WHERE sales_invoices.customer_id = customers.id AND sales_invoices.company_id = ' . $companyId . ' AND DATE(sales_invoices.invoice_date) >= \'' . $dateFrom . '\' AND DATE(sales_invoices.invoice_date) <= \'' . $dateTo . '\' AND sales_invoices.status = \'posted\' AND sales_invoices.deleted_at IS NULL ORDER BY sales_invoices.id DESC LIMIT 1)'));
            })
            ->whereNull('employees.deleted_at')
            ->whereIn('customers.id', $customerIds)
            ->select(
                'customers.id as customer_id',
                'customers.code as customer_code',
                'customers.name_ar as customer_name',
                DB::raw("TRIM(COALESCE(employees.first_name_ar, '') || ' ' || COALESCE(employees.second_name_ar, '') || ' ' || COALESCE(employees.third_name_ar, '') || ' ' || COALESCE(employees.last_name_ar, '')) as sales_rep_name"),
                'employees.id as sales_rep_id'
            );

        $customers = $customersQuery->get()->keyBy('customer_id');

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
            usort($rep['customers'], fn($a, $b) => strcmp($a['customer_name'], $b['customer_name']));
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
}
