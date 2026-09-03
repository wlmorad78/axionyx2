<?php
/**
 * =====================================================================
 * Controller: HandheldV110Controller
 * Module: Handheld V1.1.0 API
 * ---------------------------------------------------------------------
 * Description:
 * واجهة برمجة التطبيقات الجديدة للهاند هيلد الإصدار 1.1.0
 * تشمل: المصادقة، العملاء، تقرير نهاية اليوم، المزامنة، وإعدادات عامة
 * =====================================================================
 */
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class HandheldV110Controller extends Controller
{
    // ================================================================
    // AUTHENTICATION
    // ================================================================

    /**
     * POST /api/handheld-v1/login
     * تسجيل الدخول والحصول على Access Token
     */
    public function login(Request $request)
    {
        $request->validate([
            'usercode' => 'required|numeric',
            'password' => 'required',
        ]);

        $user = User::where('usercode', $request->usercode)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'usercode' => ['اسم المستخدم أو كلمة المرور غير صحيحة.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'usercode' => ['الحساب غير مفعل.'],
            ]);
        }

        $user->tokens()->delete();
        $token = $user->createToken('handheld-v1-token')->plainTextToken;

        $defaultBranch = $user->branches()->wherePivot('is_default', true)->first();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'usercode' => $user->usercode,
                    'name' => $user->name,
                ],
                'company' => $user->company ? [
                    'id' => $user->company->id,
                    'name' => $user->company->name_ar ?? $user->company->name,
                ] : null,
                'branch' => $defaultBranch ? [
                    'id' => $defaultBranch->id,
                    'name' => $defaultBranch->name_ar ?? $defaultBranch->name,
                ] : null,
                'token' => $token,
            ],
        ]);
    }

    /**
     * GET /api/handheld-v1/profile
     * جلب بيانات المندوب الشخصي
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        $employee = DB::table('employees')->where('id', $user->id)->first();
        $defaultBranch = $user->branches()->wherePivot('is_default', true)->first();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'usercode' => $user->usercode,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'employee' => $employee ? [
                    'id' => $employee->id,
                    'employee_code' => $employee->employee_code,
                    'first_name_ar' => $employee->first_name_ar,
                    'second_name_ar' => $employee->second_name_ar,
                    'third_name_ar' => $employee->third_name_ar,
                    'last_name_ar' => $employee->last_name_ar,
                    'phone' => $employee->phone,
                    'mobile' => $employee->mobile,
                ] : null,
                'company' => $user->company ? [
                    'id' => $user->company->id,
                    'name' => $user->company->name_ar ?? $user->company->name,
                    'phone' => $user->company->phone,
                    'address' => $user->company->address,
                ] : null,
                'branch' => $defaultBranch ? [
                    'id' => $defaultBranch->id,
                    'name' => $defaultBranch->name_ar ?? $defaultBranch->name,
                ] : null,
            ],
        ]);
    }

    /**
     * POST /api/handheld-v1/refresh-token
     * تحديث Access Token
     */
    public function refreshToken(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete();
        $token = $user->createToken('handheld-v1-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
            ],
        ]);
    }

    /**
     * POST /api/handheld-v1/logout
     * تسجيل الخروج وإلغاء Token
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الخروج بنجاح',
        ]);
    }

    // ================================================================
    // CUSTOMERS
    // ================================================================

    /**
     * GET /api/handheld-v1/customers
     * جلب قائمة عملاء المندوب
     */
    public function customers(Request $request)
    {
        $user = $request->user();
        $employeeId = DB::table('employees')->where('id', $user->id)->value('id');

        // جلب معرفات الطرق الخاصة بالمندوب
        $routeIds = DB::table('route_assignments')
            ->where('user_id', $employeeId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->pluck('route_id');

        // جلب معرفات العملاء من الطرق
        $customerIds = DB::table('route_customers')
            ->whereIn('route_id', $routeIds)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->pluck('customer_id')
            ->unique();

        // جلب أرصدة العملاء
        $customerBalances = $this->getCustomerBalances($user->company_id, $customerIds);

        // جلب بيانات العملاء
        $customers = DB::table('customers')
            ->whereIn('id', $customerIds)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->get(['id', 'code', 'name_ar', 'name_en', 'phone', 'mobile', 'address_line', 'latitude', 'longitude'])
            ->map(function ($c) use ($customerBalances) {
                return [
                    'id' => $c->id,
                    'code' => $c->code,
                    'name' => $c->name_ar ?? $c->name_en,
                    'phone' => $c->phone,
                    'mobile' => $c->mobile,
                    'address' => $c->address_line,
                    'latitude' => $c->latitude,
                    'longitude' => $c->longitude,
                    'balance' => $customerBalances[(int) $c->id]['balance'] ?? 0,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $customers,
        ]);
    }

    /**
     * GET /api/handheld-v1/customers/{id}
     * جلب تفاصيل عميل محدد
     */
    public function customerDetails(Request $request, int $id)
    {
        $user = $request->user();

        $customer = DB::table('customers')
            ->where('id', $id)
            ->where('company_id', $user->company_id)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->first(['id', 'code', 'name_ar', 'name_en', 'phone', 'mobile', 'address_line', 'latitude', 'longitude', 'customer_type_id']);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'العميل غير موجود',
            ], 404);
        }

        // جلب رصيد العميل
        $balance = $this->getCustomerBalances($user->company_id, [$id]);
        $customerBalance = $balance[$id] ?? ['debit_amount' => 0, 'credit_amount' => 0, 'balance' => 0];

        // جلب آخر 10 فواتير
        $recentInvoices = DB::table('sales_invoices')
            ->where('customer_id', $id)
            ->where('company_id', $user->company_id)
            ->whereNull('deleted_at')
            ->orderByDesc('invoice_date')
            ->limit(10)
            ->get(['id', 'invoice_no', 'invoice_date', 'net_total', 'paid_amount', 'status']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $customer->id,
                'code' => $customer->code,
                'name' => $customer->name_ar ?? $customer->name_en,
                'phone' => $customer->phone,
                'mobile' => $customer->mobile,
                'address' => $customer->address_line,
                'latitude' => $customer->latitude,
                'longitude' => $customer->longitude,
                'customer_type_id' => $customer->customer_type_id,
                'balance' => $customerBalance,
                'recent_invoices' => $recentInvoices,
            ],
        ]);
    }

    /**
     * GET /api/handheld-v1/customers/{id}/statement
     * كشف حساب عميل محدد
     */
    public function customerStatement(Request $request, int $id)
    {
        $user = $request->user();
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $customer = DB::table('customers')
            ->where('id', $id)
            ->where('company_id', $user->company_id)
            ->whereNull('deleted_at')
            ->first(['id', 'code', 'name_ar', 'name_en']);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'العميل غير موجود',
            ], 404);
        }

        // جلب حركات العميل (فواتير + مدفوعات)
        $transactions = DB::table('customer_ledger')
            ->where('customer_id', $id)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date')
            ->get();

        // حساب الأرصدة
        $openingBalance = DB::table('customer_ledger')
            ->where('customer_id', $id)
            ->where('transaction_date', '<', $startDate)
            ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
            ->first();

        $closingBalance = DB::table('customer_ledger')
            ->where('customer_id', $id)
            ->where('transaction_date', '<=', $endDate)
            ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'customer' => [
                    'id' => $customer->id,
                    'code' => $customer->code,
                    'name' => $customer->name_ar ?? $customer->name_en,
                ],
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'opening_balance' => [
                    'debit' => (float) $openingBalance->total_debit,
                    'credit' => (float) $openingBalance->total_credit,
                    'balance' => (float) ($openingBalance->total_debit - $openingBalance->total_credit),
                ],
                'closing_balance' => [
                    'debit' => (float) $closingBalance->total_debit,
                    'credit' => (float) $closingBalance->total_credit,
                    'balance' => (float) ($closingBalance->total_debit - $closingBalance->total_credit),
                ],
                'transactions' => $transactions,
            ],
        ]);
    }

    // ================================================================
    // END OF DAY
    // ================================================================

    /**
     * POST /api/handheld-v1/end-day/submit
     * إرسال تقرير نهاية اليوم
     */
    public function endDaySubmit(Request $request)
    {
        $user = $request->user();
        $employeeId = DB::table('employees')->where('id', $user->id)->value('id');

        $validated = $request->validate([
            'settlement_date' => 'required|date',
            'total_invoices' => 'required|integer|min:0',
            'total_sales_amount' => 'required|numeric|min:0',
            'total_collected_amount' => 'required|numeric|min:0',
            'total_expenses' => 'required|numeric|min:0',
            'actual_cash' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $settlementId = DB::table('rep_daily_settlements')->insertGetId([
            'company_id' => $user->company_id,
            'sales_rep_id' => $employeeId,
            'settlement_date' => $validated['settlement_date'],
            'settlement_no' => now()->format('Ymd') . '-' . str_pad($employeeId, 4, '0', STR_PAD_LEFT),
            'total_sales_value' => $validated['total_sales_amount'],
            'total_collections_value' => $validated['total_collected_amount'],
            'total_expenses' => $validated['total_expenses'],
            'actual_cash' => $validated['actual_cash'] ?? $validated['total_collected_amount'] - $validated['total_expenses'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'submitted',
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال تقرير نهاية اليوم بنجاح',
            'data' => [
                'settlement_id' => $settlementId,
            ],
        ], 201);
    }

    /**
     * GET /api/handheld-v1/end-day/summary
     * ملخص نهاية اليوم الحالي
     */
    public function endDaySummary(Request $request)
    {
        $user = $request->user();
        $employeeId = DB::table('employees')->where('id', $user->id)->value('id');
        $today = now()->format('Y-m-d');

        // إجمالي فواتير اليوم
        $invoicesSummary = DB::table('sales_invoices')
            ->where('company_id', $user->company_id)
            ->where('salesman_id', $employeeId)
            ->whereDate('invoice_date', $today)
            ->whereNull('deleted_at')
            ->selectRaw('COUNT(*) as total_invoices, COALESCE(SUM(net_total), 0) as total_sales, COALESCE(SUM(paid_amount), 0) as total_collected')
            ->first();

        // مصروفات اليوم
        $expenses = DB::table('vehicle_daily_expenses')
            ->where('employee_id', $employeeId)
            ->whereDate('expense_date', $today)
            ->whereNull('deleted_at')
            ->selectRaw('COALESCE(SUM(amount), 0) as total_expenses')
            ->first();

        // عدد العملاء المزورين
        $visitedCustomers = DB::table('customer_visits')
            ->where('employee_id', $employeeId)
            ->whereDate('visit_date', $today)
            ->whereNull('deleted_at')
            ->count('customer_id');

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $today,
                'total_invoices' => $invoicesSummary->total_invoices ?? 0,
                'total_sales' => (float) ($invoicesSummary->total_sales ?? 0),
                'total_collected' => (float) ($invoicesSummary->total_collected ?? 0),
                'total_expenses' => (float) ($expenses->total_expenses ?? 0),
                'net_amount' => (float) (($invoicesSummary->total_collected ?? 0) - ($expenses->total_expenses ?? 0)),
                'visited_customers' => $visitedCustomers,
            ],
        ]);
    }

    /**
     * GET /api/handheld-v1/end-day/settlements
     * معلومات التسوية
     */
    public function endDaySettlements(Request $request)
    {
        $user = $request->user();
        $employeeId = DB::table('employees')->where('id', $user->id)->value('id');

        $settlements = DB::table('rep_daily_settlements')
            ->where('company_id', $user->company_id)
            ->where('sales_rep_id', $employeeId)
            ->whereNull('deleted_at')
            ->orderByDesc('settlement_date')
            ->limit(30)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $settlements,
        ]);
    }

    /**
     * GET /api/handheld-v1/end-day/history
     * سجل تقارير الأيام السابقة
     */
    public function endDayHistory(Request $request)
    {
        $user = $request->user();
        $employeeId = DB::table('employees')->where('id', $user->id)->value('id');
        $limit = $request->input('limit', 30);

        $history = DB::table('rep_daily_settlements')
            ->where('company_id', $user->company_id)
            ->where('sales_rep_id', $employeeId)
            ->whereNull('deleted_at')
            ->orderByDesc('settlement_date')
            ->limit($limit)
            ->get([
                'id', 'settlement_date', 'settlement_no', 'total_sales_value',
                'total_collections_value', 'total_expenses', 'actual_cash', 'status'
            ]);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    // ================================================================
    // SYNC
    // ================================================================

    /**
     * POST /api/handheld-v1/sync/push
     * رفع البيانات المحلية للسيرفر
     */
    public function syncPush(Request $request)
    {
        $user = $request->user();
        $employeeId = DB::table('employees')->where('id', $user->id)->value('id');

        $validated = $request->validate([
            'invoices' => 'nullable|array',
            'invoices.*.client_uuid' => 'required|string',
            'invoices.*.customer_id' => 'required|integer',
            'invoices.*.items' => 'required|array',
            'invoices.*.items.*.item_id' => 'required|integer',
            'invoices.*.items.*.quantity' => 'required|numeric|min:0.01',
            'invoices.*.items.*.unit_price' => 'required|numeric|min:0',
            'invoices.*.total_amount' => 'required|numeric|min:0',
            'invoices.*.paid_amount' => 'required|numeric|min:0',
            'visits' => 'nullable|array',
            'expenses' => 'nullable|array',
        ]);

        $results = [
            'synced_invoices' => 0,
            'synced_visits' => 0,
            'synced_expenses' => 0,
            'errors' => [],
        ];

        // مزامنة الفواتير
        if (!empty($validated['invoices'])) {
            foreach ($validated['invoices'] as $invoice) {
                try {
                    $this->syncInvoice($user, $employeeId, $invoice);
                    $results['synced_invoices']++;
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'type' => 'invoice',
                        'uuid' => $invoice['client_uuid'] ?? null,
                        'message' => $e->getMessage(),
                    ];
                }
            }
        }

        // مزامنة الزيارات
        if (!empty($validated['visits'])) {
            foreach ($validated['visits'] as $visit) {
                try {
                    $this->syncVisit($user, $employeeId, $visit);
                    $results['synced_visits']++;
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'type' => 'visit',
                        'message' => $e->getMessage(),
                    ];
                }
            }
        }

        // مزامنة المصروفات
        if (!empty($validated['expenses'])) {
            foreach ($validated['expenses'] as $expense) {
                try {
                    $this->syncExpense($user, $employeeId, $expense);
                    $results['synced_expenses']++;
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'type' => 'expense',
                        'message' => $e->getMessage(),
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * POST /api/handheld-v1/sync/pull
     * تحميل البيانات من السيرفر
     */
    public function syncPull(Request $request)
    {
        $user = $request->user();
        $employeeId = DB::table('employees')->where('id', $user->id)->value('id');
        $lastSync = $request->input('last_sync');

        // جلب البيانات المحدثة منذ آخر مزامنة
        $query = fn ($table) => DB::table($table)
            ->where('company_id', $user->company_id)
            ->whereNull('deleted_at');

        if ($lastSync) {
            $query = fn ($table) => $query($table)->where('updated_at', '>', $lastSync);
        }

        // جلب العملاء
        $routeIds = DB::table('route_assignments')
            ->where('user_id', $employeeId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->pluck('route_id');

        $customerIds = DB::table('route_customers')
            ->whereIn('route_id', $routeIds)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->pluck('customer_id')
            ->unique();

        $customers = DB::table('customers')
            ->whereIn('id', $customerIds)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->get(['id', 'code', 'name_ar', 'name_en', 'phone', 'mobile', 'address_line']);

        // جلب المنتجات
        $items = DB::table('items')
            ->where('company_id', $user->company_id)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->get(['id', 'code', 'name_ar', 'name_en']);

        // جلب أسعار المنتجات
        $itemIds = $items->pluck('id')->toArray();
        $prices = DB::table('item_units')
            ->whereIn('item_id', $itemIds)
            ->get(['item_id', 'unit_id', 'sale_price', 'is_sales_unit']);

        $priceMap = [];
        foreach ($prices as $price) {
            if ((float) $price->sale_price > 0) {
                if ($price->is_sales_unit && !isset($priceMap[$price->item_id])) {
                    $priceMap[$price->item_id] = (float) $price->sale_price;
                } elseif (!isset($priceMap[$price->item_id])) {
                    $priceMap[$price->item_id] = (float) $price->sale_price;
                }
            }
        }

        $itemsWithPrices = $items->map(function ($item) use ($priceMap) {
            return [
                'id' => $item->id,
                'code' => $item->code,
                'name' => $item->name_ar ?? $item->name_en,
                'sale_price' => $priceMap[$item->id] ?? 0,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'customers' => $customers,
                'items' => $itemsWithPrices,
                'synced_at' => now()->toDateTimeString(),
            ],
        ]);
    }

    /**
     * GET /api/handheld-v1/sync/status
     * حالة المزامنة الأخيرة
     */
    public function syncStatus(Request $request)
    {
        $user = $request->user();
        $employeeId = DB::table('employees')->where('id', $user->id)->value('id');

        $lastSync = DB::table('sync_logs')
            ->where('company_id', $user->company_id)
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->first(['created_at', 'status', 'synced_records']);

        return response()->json([
            'success' => true,
            'data' => [
                'last_sync' => $lastSync ? [
                    'date' => $lastSync->created_at,
                    'status' => $lastSync->status,
                    'records' => $lastSync->synced_records,
                ] : null,
                'pending_sync' => $this->getPendingSyncCount($user, $employeeId),
            ],
        ]);
    }

    // ================================================================
    // GENERAL
    // ================================================================

    /**
     * GET /api/handheld-v1/health
     * فحص صحة السيرفر
     */
    public function health()
    {
        return response()->json([
            'success' => true,
            'status' => 'healthy',
            'timestamp' => now()->toDateTimeString(),
            'version' => '1.1.0',
        ]);
    }

    /**
     * GET /api/handheld-v1/version
     * إصدار التطبيق
     */
    public function version()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'version' => '1.1.0',
                'build_number' => 1,
                'min_required_version' => '1.1.0',
                'force_update' => false,
                'release_notes' => 'الإصدار الجديد مع واجهة برمجة تطبيقات محسنة',
                'download_url' => null,
            ],
        ]);
    }

    /**
     * GET /api/handheld-v1/salesman-profile
     * ملف المندوب الشخصي
     */
    public function salesmanProfile(Request $request)
    {
        return $this->profile($request);
    }

    /**
     * GET /api/handheld-v1/routes
     * جلب طرق المندوب
     */
    public function routes(Request $request)
    {
        $user = $request->user();
        $employeeId = DB::table('employees')->where('id', $user->id)->value('id');

        $routes = DB::table('routes')
            ->whereIn('routes.id', function ($query) use ($employeeId) {
                $query->select('route_id')
                    ->from('route_assignments')
                    ->where('user_id', $employeeId)
                    ->where('is_active', true)
                    ->whereNull('deleted_at');
            })
            ->where('routes.is_active', true)
            ->whereNull('routes.deleted_at')
            ->leftJoin('sales_territories', 'routes.sales_territory_id', '=', 'sales_territories.id')
            ->select('routes.id', 'routes.code', 'routes.name_ar', 'routes.name_en', 'routes.sales_territory_id', 'sales_territories.name_ar as territory_name')
            ->get()
            ->map(function ($route) {
                $customerCount = DB::table('route_customers')
                    ->where('route_id', $route->id)
                    ->where('is_active', true)
                    ->whereNull('deleted_at')
                    ->count();

                return [
                    'id' => $route->id,
                    'code' => $route->code,
                    'name' => $route->name_ar ?? $route->name_en,
                    'territory_name' => $route->territory_name,
                    'customer_count' => $customerCount,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $routes,
        ]);
    }

    /**
     * GET /api/handheld-v1/stock
     * جلب مخزون السيارة
     */
    public function stock(Request $request)
    {
        $user = $request->user();
        $employeeId = DB::table('employees')->where('id', $user->id)->value('id');

        $stock = DB::table('rep_item_distributions as d')
            ->join('items as i', 'i.id', '=', 'd.item_id')
            ->where('d.company_id', $user->company_id)
            ->where('d.user_id', $employeeId)
            ->where('d.status', 'active')
            ->where('d.remaining_qty', '>', 0)
            ->select('d.item_id', 'i.code', 'i.name_ar', 'i.name_en', DB::raw('SUM(d.remaining_qty) as available_qty'))
            ->groupBy('d.item_id', 'i.code', 'i.name_ar', 'i.name_en')
            ->orderBy('i.name_ar')
            ->get()
            ->map(function ($item) {
                $price = DB::table('item_units')
                    ->where('item_id', $item->item_id)
                    ->where('is_sales_unit', true)
                    ->where('sale_price', '>', 0)
                    ->value('sale_price');

                return [
                    'item_id' => $item->item_id,
                    'item_code' => $item->code,
                    'item_name' => $item->name_ar ?? $item->name_en,
                    'available_qty' => (float) $item->available_qty,
                    'unit_price' => (float) ($price ?? 0),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $stock,
        ]);
    }

    /**
     * GET /api/handheld-v1/bank-accounts
     * جلب الحسابات البنكية
     */
    public function bankAccounts(Request $request)
    {
        $user = $request->user();

        $accounts = DB::table('bank_accounts')
            ->where('company_id', $user->company_id)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->get(['id', 'account_name', 'account_number', 'bank_name', 'branch_name']);

        return response()->json([
            'success' => true,
            'data' => $accounts,
        ]);
    }

    // ================================================================
    // PRIVATE HELPERS
    // ================================================================

    /**
     * حساب أرصدة العملاء
     */
    private function getCustomerBalances($companyId, $customerIds)
    {
        if (empty($customerIds)) {
            return [];
        }

        $invoiceBalances = DB::table('sales_invoices')
            ->where('company_id', $companyId)
            ->whereIn('customer_id', $customerIds)
            ->whereNull('deleted_at')
            ->selectRaw('customer_id, COALESCE(SUM(net_total), 0) as debit_amount, COALESCE(SUM(paid_amount), 0) as credit_amount')
            ->groupBy('customer_id')
            ->pluck('debit_amount', 'customer_id')
            ->toArray();

        $ledgerBalances = DB::table('customer_ledger')
            ->whereIn('customer_id', array_keys($invoiceBalances))
            ->selectRaw('customer_id, COALESCE(SUM(credit), 0) as credit')
            ->groupBy('customer_id')
            ->pluck('credit', 'customer_id')
            ->toArray();

        $balanceMap = [];
        foreach ($invoiceBalances as $customerId => $debit) {
            $credit = $ledgerBalances[$customerId] ?? 0;
            $balanceMap[$customerId] = [
                'debit_amount' => (float) $debit,
                'credit_amount' => (float) $credit,
                'balance' => (float) ($debit - $credit),
            ];
        }

        return $balanceMap;
    }

    /**
     * مزامنة فاتورة واحدة
     */
    private function syncInvoice($user, $employeeId, $invoice)
    {
        DB::beginTransaction();

        try {
            // إنشاء الفاتورة
            $invoiceId = DB::table('sales_invoices')->insertGetId([
                'company_id' => $user->company_id,
                'customer_id' => $invoice['customer_id'],
                'salesman_id' => $employeeId,
                'invoice_no' => $this->generateInvoiceNo($user, $invoice['client_uuid']),
                'invoice_date' => now()->format('Y-m-d'),
                'net_total' => $invoice['total_amount'],
                'paid_amount' => $invoice['paid_amount'],
                'status' => 'posted',
                'client_uuid' => $invoice['client_uuid'],
                'created_by' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // إضافة الأصناف
            foreach ($invoice['items'] as $item) {
                DB::table('sales_invoice_items')->insert([
                    'sales_invoice_id' => $invoiceId,
                    'item_id' => $item['item_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $item['quantity'] * $item['unit_price'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * مزامنة زيارة
     */
    private function syncVisit($user, $employeeId, $visit)
    {
        DB::table('customer_visits')->insert([
            'company_id' => $user->company_id,
            'employee_id' => $employeeId,
            'customer_id' => $visit['customer_id'],
            'visit_date' => $visit['visit_date'] ?? now()->format('Y-m-d'),
            'visit_time' => $visit['visit_time'] ?? now()->format('H:i:s'),
            'latitude' => $visit['latitude'] ?? null,
            'longitude' => $visit['longitude'] ?? null,
            'notes' => $visit['notes'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * مزامنة مصروف
     */
    private function syncExpense($user, $employeeId, $expense)
    {
        DB::table('vehicle_daily_expenses')->insert([
            'company_id' => $user->company_id,
            'employee_id' => $employeeId,
            'vehicle_id' => $expense['vehicle_id'] ?? null,
            'expense_type' => $expense['expense_type'],
            'amount' => $expense['amount'],
            'km' => $expense['km'] ?? null,
            'quantity' => $expense['quantity'] ?? null,
            'notes' => $expense['notes'] ?? null,
            'expense_date' => $expense['expense_date'] ?? now()->format('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * توليد رقم الفاتورة
     */
    private function generateInvoiceNo($user, $clientUuid)
    {
        $yy = now()->format('y');
        $mm = now()->format('m');
        $dd = now()->format('d');
        $salesmanCode = $user->usercode ?? '000';

        $lastSequence = DB::table('sales_invoices')
            ->where('company_id', $user->company_id)
            ->whereDate('invoice_date', now()->format('Y-m-d'))
            ->where('salesman_id', $user->id)
            ->count();

        $sequence = str_pad($lastSequence + 1, 3, '0', STR_PAD_LEFT);

        return "{$yy}{$mm}{$dd}-{$salesmanCode}-{$sequence}";
    }

    /**
     * عدد السجلات المعلقة للمزامنة
     */
    private function getPendingSyncCount($user, $employeeId)
    {
        $pendingInvoices = DB::table('sales_invoices')
            ->where('company_id', $user->company_id)
            ->where('salesman_id', $employeeId)
            ->where('status', 'pending')
            ->count();

        $pendingVisits = DB::table('customer_visits')
            ->where('company_id', $user->company_id)
            ->where('employee_id', $employeeId)
            ->where('synced', false)
            ->count();

        $pendingExpenses = DB::table('vehicle_daily_expenses')
            ->where('company_id', $user->company_id)
            ->where('employee_id', $employeeId)
            ->where('synced', false)
            ->count();

        return [
            'invoices' => $pendingInvoices,
            'visits' => $pendingVisits,
            'expenses' => $pendingExpenses,
            'total' => $pendingInvoices + $pendingVisits + $pendingExpenses,
        ];
    }
}
