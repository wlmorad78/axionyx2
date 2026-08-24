<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Handheld2Controller;

Route::post('handheld2/login', [Handheld2Controller::class, 'login']);
Route::get('handheld2/health', [Handheld2Controller::class, 'health']);

Route::get('handheld2/version', function () {
    return response()->json([
        'success' => true,
        'data' => [
            'version' => '1.0.2',
            'build_number' => 3,
            'min_required_version' => '1.0.0',
            'force_update' => false,
            'release_notes' => 'إصلاحات وتحسينات عامة',
            'download_url' => 'http://207.231.110.79/apps/hh/android/releases/axionyx_hh_v1.0.2.apk',
        ],
    ]);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('handheld2/salesman-profile', [Handheld2Controller::class, 'salesmanProfile']);
    Route::get('handheld2/start-day-counts', [Handheld2Controller::class, 'startDayCounts']);
    Route::get('handheld2/routes-with-customers', [Handheld2Controller::class, 'routesWithCustomers']);
    Route::get('handheld2/next-customer-code', [Handheld2Controller::class, 'nextCustomerCode']);
    Route::get('handheld2/download-data', [Handheld2Controller::class, 'downloadData']);
    Route::get('handheld2/load-orders', [Handheld2Controller::class, 'loadOrders']);
    Route::post('handheld2/sync/push', [Handheld2Controller::class, 'syncPush']);
    Route::post('handheld2/sync/pull', [Handheld2Controller::class, 'syncPull']);
    Route::post('handheld2/start-day', [Handheld2Controller::class, 'startDay']);

    Route::post('handheld2/car-expenses', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        $employee = DB::table('employees')->where('user_id', $user->id)->first();

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
});