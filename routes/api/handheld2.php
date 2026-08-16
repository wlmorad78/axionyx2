<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Handheld2Controller;

Route::post('handheld2/login', [Handheld2Controller::class, 'login']);
Route::get('handheld2/health', [Handheld2Controller::class, 'health']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('handheld2/salesman-profile', [Handheld2Controller::class, 'salesmanProfile']);
    Route::get('handheld2/start-day-counts', [Handheld2Controller::class, 'startDayCounts']);
    Route::get('handheld2/routes-with-customers', [Handheld2Controller::class, 'routesWithCustomers']);
    Route::get('handheld2/next-customer-code', [Handheld2Controller::class, 'nextCustomerCode']);
    Route::get('handheld2/download-data', [Handheld2Controller::class, 'downloadData']);
    Route::get('handheld2/load-orders', [Handheld2Controller::class, 'loadOrders']);
    Route::post('handheld2/sync/push', [Handheld2Controller::class, 'syncPush']);
    Route::post('handheld2/sync/pull', [Handheld2Controller::class, 'syncPull']);

    Route::post('handheld2/car-expenses', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        $employee = DB::table('employees')->where('user_id', $user->id)->first();

        $validated = $request->validate([
            'expense_type' => 'required|string|max:50',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'expense_date' => 'required|date',
        ]);

        $vehicleId = null;
        if ($employee) {
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
            'expense_date' => $validated['expense_date'],
            'expense_type' => strtoupper($validated['expense_type']),
            'amount' => $validated['amount'],
            'notes' => $validated['notes'] ?? null,
            'created_by' => $user->id,
        ]);

        return response()->json([
            'message' => 'تم إضافة المصروف بنجاح',
            'data' => $expense,
        ], 201);
    });
});