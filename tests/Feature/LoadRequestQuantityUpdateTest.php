<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Fleet\LoadRequestController;
use App\Models\IssueOrder;
use App\Models\LoadRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LoadRequestQuantityUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_adjusting_issued_quantity_updates_warehouse_and_representative_stock(): void
    {
        $now = now();
        $companyId = DB::table('companies')->insertGetId([
            'code' => 'LQ' . uniqid(),
            'name' => 'Load quantity test',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $branchId = DB::table('branches')->insertGetId([
            'company_id' => $companyId,
            'code' => 'B' . uniqid(),
            'name' => 'Test branch',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $warehouseId = DB::table('warehouses')->insertGetId([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'code' => 'W' . uniqid(),
            'name' => 'Test warehouse',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $userId = DB::table('users')->insertGetId([
            'usercode' => random_int(100000, 999999),
            'name' => 'Load representative',
            'password' => bcrypt('password'),
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $employeeId = DB::table('employees')->insertGetId([
            'company_id' => $companyId,
            'user_id' => $userId,
            'employee_code' => 'E' . uniqid(),
            'first_name_ar' => 'مندوب',
            'last_name_ar' => 'اختبار',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $unitId = DB::table('units')->insertGetId([
            'code' => 'U' . uniqid(),
            'name_ar' => 'وحدة',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $itemId = DB::table('items')->insertGetId([
            'company_id' => $companyId,
            'code' => 'I' . uniqid(),
            'name_ar' => 'صنف اختبار',
            'base_unit_id' => $unitId,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $loadRequestId = DB::table('load_requests')->insertGetId([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'warehouse_id' => $warehouseId,
            'request_no' => 'LR' . uniqid(),
            'employee_id' => $employeeId,
            'user_id' => $userId,
            'request_date' => $now->toDateString(),
            'status' => 'loading',
            'total_items_count' => 1,
            'total_quantity' => 10,
            'total_amount' => 20,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $loadItemId = DB::table('load_request_items')->insertGetId([
            'load_request_id' => $loadRequestId,
            'item_id' => $itemId,
            'unit_id' => $unitId,
            'quantity' => 10,
            'conversion_factor' => 1,
            'base_quantity' => 10,
            'unit_price' => 2,
            'total_price' => 20,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $issueOrderId = DB::table('issue_orders')->insertGetId([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'warehouse_id' => $warehouseId,
            'load_request_id' => $loadRequestId,
            'issue_no' => 'IO' . uniqid(),
            'issue_date' => $now->toDateString(),
            'issue_time' => $now->format('H:i:s'),
            'employee_id' => $employeeId,
            'user_id' => $userId,
            'status' => 'issued',
            'total_items_count' => 1,
            'total_quantity' => 10,
            'total_amount' => 20,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('issue_order_items')->insert([
            'issue_order_id' => $issueOrderId,
            'item_id' => $itemId,
            'unit_id' => $unitId,
            'conversion_factor' => 1,
            'base_quantity' => 10,
            'requested_quantity' => 10,
            'issued_quantity' => 10,
            'purchase_price' => 2,
            'sales_price' => 2,
            'total_amount' => 20,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('rep_item_distributions')->insert([
            'company_id' => $companyId,
            'employee_id' => $employeeId,
            'user_id' => $userId,
            'item_id' => $itemId,
            'issue_order_id' => $issueOrderId,
            'loaded_qty' => 10,
            'sold_qty' => 3,
            'returned_qty' => 1,
            'remaining_qty' => 6,
            'unit_price' => 2,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $additionTypeId = DB::table('inventory_transaction_types')->insertGetId([
            'code' => 'OPENING_BALANCE',
            'name' => 'رصيد افتتاحي',
            'effect' => 'addition',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $issueTypeId = DB::table('inventory_transaction_types')->insertGetId([
            'code' => 'ISSUE_ORDER',
            'name' => 'أمر صرف',
            'effect' => 'subtraction',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        foreach ([
            [$additionTypeId, 50, null],
            [$issueTypeId, -10, IssueOrder::class],
        ] as $index => [$typeId, $quantity, $referenceType]) {
            $transactionId = DB::table('inventory_transactions')->insertGetId([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'transaction_type_id' => $typeId,
                'warehouse_id' => $warehouseId,
                'transaction_no' => 'T' . uniqid(),
                'transaction_date' => $now->toDateString(),
                'transaction_time' => $now->format('H:i:s'),
                'reference_type' => $referenceType,
                'reference_id' => $referenceType ? $issueOrderId : null,
                'status' => 'posted',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('inventory_transaction_items')->insert([
                'inventory_transaction_id' => $transactionId,
                'item_id' => $itemId,
                'unit_id' => $unitId,
                'conversion_factor' => 1,
                'qty' => $quantity,
                'unit_cost' => 2,
                'total_cost' => abs($quantity) * 2,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $request = Request::create('/api/load-requests/' . $loadRequestId, 'PATCH', [
            'notes' => 'تعديل الكمية',
            'items' => [[
                'item_id' => $itemId,
                'quantity' => 8,
                'unit_id' => $unitId,
                'unit_price' => 2,
            ]],
        ]);
        $response = app(LoadRequestController::class)->update(
            $request,
            LoadRequest::findOrFail($loadRequestId)
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(8.0, (float) DB::table('load_request_items')->where('id', $loadItemId)->value('quantity'));
        $this->assertSame(8.0, (float) DB::table('issue_order_items')->where('issue_order_id', $issueOrderId)->value('issued_quantity'));
        $this->assertSame(8.0, (float) DB::table('rep_item_distributions')->where('issue_order_id', $issueOrderId)->value('loaded_qty'));
        $this->assertSame(4.0, (float) DB::table('rep_item_distributions')->where('issue_order_id', $issueOrderId)->value('remaining_qty'));
        $this->assertSame(42.0, (float) DB::table('inventory_transaction_items')
            ->where('item_id', $itemId)
            ->sum('qty'));

        $addedItemId = DB::table('items')->insertGetId([
            'company_id' => $companyId,
            'code' => 'IA' . uniqid(),
            'name_ar' => 'صنف إضافي للاختبار',
            'base_unit_id' => $unitId,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $openingTransactionId = DB::table('inventory_transactions')->insertGetId([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'transaction_type_id' => $additionTypeId,
            'warehouse_id' => $warehouseId,
            'transaction_no' => 'T' . uniqid(),
            'transaction_date' => $now->toDateString(),
            'transaction_time' => $now->format('H:i:s'),
            'status' => 'posted',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('inventory_transaction_items')->insert([
            'inventory_transaction_id' => $openingTransactionId,
            'item_id' => $addedItemId,
            'unit_id' => $unitId,
            'conversion_factor' => 1,
            'qty' => 20,
            'unit_cost' => 2,
            'total_cost' => 40,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $addItemResponse = app(LoadRequestController::class)->update(
            Request::create('/api/load-requests/' . $loadRequestId, 'PATCH', [
                'items' => [
                    [
                        'item_id' => $itemId,
                        'quantity' => 8,
                        'unit_id' => $unitId,
                        'unit_price' => 2,
                    ],
                    [
                        'item_id' => $addedItemId,
                        'quantity' => 5,
                        'unit_id' => $unitId,
                        'unit_price' => 2,
                    ],
                ],
            ]),
            LoadRequest::findOrFail($loadRequestId)
        );
        $this->assertSame(200, $addItemResponse->getStatusCode());
        $this->assertDatabaseHas('load_request_items', [
            'load_request_id' => $loadRequestId,
            'item_id' => $addedItemId,
            'quantity' => 5,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('issue_order_items', [
            'issue_order_id' => $issueOrderId,
            'item_id' => $addedItemId,
            'issued_quantity' => 5,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('rep_item_distributions', [
            'issue_order_id' => $issueOrderId,
            'item_id' => $addedItemId,
            'loaded_qty' => 5,
            'remaining_qty' => 5,
            'deleted_at' => null,
        ]);
        $this->assertSame(15.0, (float) DB::table('inventory_transaction_items')
            ->where('item_id', $addedItemId)
            ->sum('qty'));

        $removeItemResponse = app(LoadRequestController::class)->update(
            Request::create('/api/load-requests/' . $loadRequestId, 'PATCH', [
                'items' => [[
                    'item_id' => $itemId,
                    'quantity' => 8,
                    'unit_id' => $unitId,
                    'unit_price' => 2,
                ]],
            ]),
            LoadRequest::findOrFail($loadRequestId)
        );
        $this->assertSame(200, $removeItemResponse->getStatusCode());
        $this->assertSoftDeleted('load_request_items', [
            'load_request_id' => $loadRequestId,
            'item_id' => $addedItemId,
        ]);
        $this->assertSoftDeleted('issue_order_items', [
            'issue_order_id' => $issueOrderId,
            'item_id' => $addedItemId,
        ]);
        $this->assertDatabaseHas('rep_item_distributions', [
            'issue_order_id' => $issueOrderId,
            'item_id' => $addedItemId,
            'loaded_qty' => 0,
            'remaining_qty' => 0,
            'deleted_at' => null,
        ]);
        $this->assertSame(20.0, (float) DB::table('inventory_transaction_items')
            ->where('item_id', $addedItemId)
            ->sum('qty'));

        try {
            app(LoadRequestController::class)->update(
                Request::create('/api/load-requests/' . $loadRequestId, 'PATCH', [
                    'items' => [[
                        'item_id' => $itemId,
                        'quantity' => 3,
                        'unit_id' => $unitId,
                        'unit_price' => 2,
                    ]],
                ]),
                LoadRequest::findOrFail($loadRequestId)
            );
            $this->fail('Quantity must not be reduced below sold and returned stock.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('items', $error->errors());
        }

        $removeUsedItemResponse = app(LoadRequestController::class)->update(
            Request::create('/api/load-requests/' . $loadRequestId, 'PATCH', [
                'items' => [[
                    'item_id' => $addedItemId,
                    'quantity' => 5,
                    'unit_id' => $unitId,
                    'unit_price' => 2,
                ]],
            ]),
            LoadRequest::findOrFail($loadRequestId)
        );
        $this->assertSame(200, $removeUsedItemResponse->getStatusCode());
        $this->assertSoftDeleted('load_request_items', ['id' => $loadItemId]);
        $this->assertSoftDeleted('issue_order_items', [
            'issue_order_id' => $issueOrderId,
            'item_id' => $itemId,
        ]);
        $this->assertDatabaseHas('rep_item_distributions', [
            'issue_order_id' => $issueOrderId,
            'item_id' => $itemId,
            'loaded_qty' => 4,
            'sold_qty' => 3,
            'returned_qty' => 1,
            'remaining_qty' => 0,
            'deleted_at' => null,
        ]);
        $this->assertSame(46.0, (float) DB::table('inventory_transaction_items')
            ->where('item_id', $itemId)
            ->sum('qty'));
    }
}
