<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Handheld2Controller;

Route::post('handheld2/login', [Handheld2Controller::class, 'login']);
Route::get('handheld2/health', [Handheld2Controller::class, 'health']);

Route::get('handheld2/version', function () {
    return response()->json([
        'success' => true,
        'data' => [
            'version' => '1.9.2',
            'build_number' => 1,
            'min_required_version' => '1.9.2',
            'force_update' => false,
            'release_notes' => 'تحسينات المزامنة والأوامر التكميلية',
            'download_url' => 'http://207.231.110.79/public/apps/hh/android/releases/axionyx_hh_v1.9.2.apk',
        ],
    ]);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('handheld2/salesman-profile', [Handheld2Controller::class, 'salesmanProfile']);
    Route::get('handheld2/start-day-counts', [Handheld2Controller::class, 'startDayCounts']);
    Route::get('handheld2/routes-with-customers', [Handheld2Controller::class, 'routesWithCustomers']);
    Route::get('handheld2/next-customer-code', [Handheld2Controller::class, 'nextCustomerCode']);
    Route::get('handheld2/download-data', [Handheld2Controller::class, 'downloadData']);
    Route::get('handheld2/bank-accounts', [Handheld2Controller::class, 'bankAccounts']);
    Route::get('handheld2/load-orders', [Handheld2Controller::class, 'loadOrders']);
    Route::post('handheld2/load-orders/{loadRequestNo}/load', [Handheld2Controller::class, 'loadOrder']);
    Route::get('handheld2/complementary-orders', [Handheld2Controller::class, 'complementaryOrders']);
    Route::get('handheld2/representatives', [Handheld2Controller::class, 'representatives']);
    Route::get('handheld2/representative-stock-summary', [Handheld2Controller::class, 'representativeStockSummary']);
    Route::get('handheld2/representative-stock/{employeeId}', [Handheld2Controller::class, 'representativeStock']);
    Route::post('handheld2/representative-transfers', [Handheld2Controller::class, 'representativeTransfer']);
    Route::get('handheld2/representative-transfers/incoming', [Handheld2Controller::class, 'incomingRepresentativeTransfers']);
    Route::post('handheld2/representative-transfers/{id}/receive', [Handheld2Controller::class, 'receiveRepresentativeTransfer']);
    Route::patch('handheld2/load-requests/{id}/status', [Handheld2Controller::class, 'updateLoadRequestStatus']);
    Route::post('handheld2/load-requests/{id}/cancel', [Handheld2Controller::class, 'cancelLoadRequest']);
    Route::post('handheld2/sync/push', [Handheld2Controller::class, 'syncPush']);
    Route::post('handheld2/sync/pull', [Handheld2Controller::class, 'syncPull']);
    Route::post('handheld2/start-day', [Handheld2Controller::class, 'startDay']);
    Route::get('handheld2/customer-statement', [Handheld2Controller::class, 'customerStatement']);
    Route::get('handheld2/customer-sales-report', [Handheld2Controller::class, 'customerSalesReport']);
    Route::get('handheld2/invoice-details', [Handheld2Controller::class, 'invoiceDetails']);
    Route::get('handheld2/invoice-payment-methods/{clientUuid}', [Handheld2Controller::class, 'invoicePaymentMethods']);

    Route::post('handheld2/car-expenses', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        $employee = DB::table('employees')->where('id', $user->id)->first();

        $validated = $request->validate([
            'vehicle_id' => 'nullable|integer',
            'expense_type' => 'required|string|max:50',
            'amount' => 'required|numeric|min:0',
            'km' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'expense_date' => 'required|date',
            'expense_time' => 'nullable|date_format:H:i',
        ]);

        $vehicleId = $validated['vehicle_id'] ?? null;
        if (!$vehicleId && $employee) {
            $assignment = DB::table('vehicle_assignments')
                ->where('sales_rep_id', $employee->id)
                ->where('status', 'active')
                ->first();
            if ($assignment) {
                $vehicleId = $assignment->vehicle_id;
            }
        }

        $expense = \App\Models\VehicleDailyExpense::create([
            'vehicle_id' => $vehicleId,
            'employee_id' => $employee?->id,
            'uuid' => $validated['uuid'] ?? null,
            'expense_date' => $validated['expense_date'],
            'expense_time' => $validated['expense_time'] ?? now()->format('H:i'),
            'expense_type' => strtoupper($validated['expense_type']),
            'amount' => $validated['amount'],
            'km' => $validated['km'] ?? null,
            'quantity' => $validated['quantity'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'created_by' => $user->id,
        ]);

        if (strtoupper($validated['expense_type']) === 'FUEL' && $vehicleId) {
            \App\Models\VehicleFuelTransaction::create([
                'vehicle_id' => $vehicleId,
                'transaction_date' => $validated['expense_date'],
                'transaction_time' => $validated['expense_time'] ?? now()->format('H:i'),
                'odometer' => $validated['km'] ?? null,
                'fuel_qty' => $validated['quantity'] ?? 0,
                'fuel_cost' => $validated['amount'],
                'notes' => $validated['notes'] ?? null,
            ]);
        }

        return response()->json([
            'message' => 'تم إضافة المصروف بنجاح',
            'data' => $expense,
        ], 201);
    });

    Route::get('handheld2/sales-territories', function (\Illuminate\Http\Request $request) {
        $user = $request->user();

        $territories = DB::table('sales_territories')
            ->where('company_id', $user->company_id)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->select('id', DB::raw('name_ar as name'), 'code')
            ->orderBy('name_ar')
            ->get();

        return response()->json(['data' => $territories]);
    });

    Route::get('handheld2/routes-by-territory', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        $territoryId = $request->input('territory_id');

        if (!$territoryId) {
            return response()->json(['message' => 'territory_id مطلوب'], 422);
        }

        $customerBalances = DB::table('sales_invoices')
            ->where('company_id', $user->company_id)
            ->whereNull('deleted_at')
            ->selectRaw('customer_id, COALESCE(SUM(net_total),0) as debit_amount, COALESCE(SUM(paid_amount),0) as credit_amount')
            ->groupBy('customer_id')
            ->pluck('debit_amount', 'customer_id')
            ->toArray();

        $creditBalances = DB::table('customer_ledger')
            ->where('customer_id', array_keys($customerBalances))
            ->selectRaw('customer_id, COALESCE(SUM(credit),0) as credit')
            ->groupBy('customer_id')
            ->pluck('credit', 'customer_id')
            ->toArray();

        $balanceMap = [];
        foreach ($customerBalances as $cid => $debit) {
            $credit = $creditBalances[$cid] ?? 0;
            $balanceMap[$cid] = [
                'debit_amount' => $debit,
                'credit_amount' => $credit,
                'balance' => $debit - $credit,
            ];
        }

        $routes = DB::table('routes')
            ->where('routes.sales_territory_id', $territoryId)
            ->where('routes.is_active', true)
            ->whereNull('routes.deleted_at')
            ->select('routes.id', 'routes.code', 'routes.name_ar', 'routes.name_en', 'routes.sales_territory_id')
            ->get()
            ->map(function ($route) use ($balanceMap) {
                $customerIds = DB::table('route_customers')
                    ->where('route_id', $route->id)
                    ->where('is_active', true)
                    ->whereNull('deleted_at')
                    ->pluck('customer_id');

                $customers = DB::table('customers')
                    ->whereIn('id', $customerIds)
                    ->where('is_active', true)
                    ->whereNull('deleted_at')
                    ->get(['id', 'code', 'name_ar', 'name_en', 'phone', 'mobile', 'address_line'])
                    ->map(function ($c) use ($balanceMap) {
                        return [
                            'id' => $c->id,
                            'code' => $c->code,
                            'name' => $c->name_ar ?? $c->name_en,
                            'phone' => $c->phone,
                            'mobile' => $c->mobile,
                            'address' => $c->address_line,
                            'customer_type_id' => 0,
                            'debit_amount' => $balanceMap[(int) $c->id]['debit_amount'] ?? 0,
                            'credit_amount' => $balanceMap[(int) $c->id]['credit_amount'] ?? 0,
                            'balance' => $balanceMap[(int) $c->id]['balance'] ?? 0,
                        ];
                    });

                $territory = DB::table('sales_territories')
                    ->where('id', $route->sales_territory_id)
                    ->first();

                return [
                    'id' => $route->id,
                    'code' => $route->code,
                    'name' => $route->name_ar ?? $route->name_en ?? '',
                    'territory_name' => $territory?->name_ar ?? '',
                    'sales_territory_id' => $route->sales_territory_id ?? 0,
                    'customers' => $customers,
                ];
            })
            ->toArray();

        return response()->json(['routes' => $routes]);
    });
});
