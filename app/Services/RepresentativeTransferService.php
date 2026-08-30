<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionItem;
use App\Models\RepresentativeTransfer;
use App\Models\RepItemDistribution;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RepresentativeTransferService
{
    public function post($user, array $data): RepresentativeTransfer
    {
        return DB::transaction(function () use ($user, $data) {
            $companyId = $user->company_id;
            $fromId = (int) $data['from_user_id'];
            $toId = (int) $data['to_user_id'];

            if ($fromId === $toId) {
                throw ValidationException::withMessages(['to_user_id' => 'لا يمكن التحويل لنفس المندوب.']);
            }

            $employees = Employee::whereIn('id', [$fromId, $toId])
                ->where('company_id', $companyId)->get()->keyBy('id');
            if ($employees->count() !== 2) {
                throw ValidationException::withMessages(['user_id' => 'المندوب غير تابع للشركة الحالية.']);
            }

            if (!empty($data['client_uuid'])) {
                $existing = RepresentativeTransfer::where('company_id', $companyId)
                    ->where('client_uuid', $data['client_uuid'])->with('items.item')->first();
                if ($existing) return $existing;
            }

            $transfer = RepresentativeTransfer::create([
                'company_id' => $companyId,
                'branch_id' => $data['branch_id'] ?? null,
                'transfer_no' => $this->nextNumber($companyId),
                'client_uuid' => $data['client_uuid'] ?? null,
                'from_employee_id' => $fromId,
                'to_employee_id' => $toId,
                'status' => 'posted',
                'created_by' => $user->id,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'posted_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            $type = DB::table('inventory_transaction_types')->where('code', 'REP_TRANSFER')->first();
            if (!$type) {
                $typeId = DB::table('inventory_transaction_types')->insertGetId([
                    'code' => 'REP_TRANSFER', 'name' => 'تحويل بين مندوبين',
                    'effect' => 'neutral', 'is_active' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            } else {
                $typeId = $type->id;
            }

            $transaction = InventoryTransaction::create([
                'company_id' => $companyId,
                'branch_id' => $data['branch_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'] ?? $this->employeeWarehouse($fromId) ?? DB::table('warehouses')->where('company_id', $companyId)->value('id'),
                'transaction_type_id' => $typeId,
                'transaction_date' => now()->toDateString(),
                'transaction_time' => now()->format('H:i:s'),
                'reference_type' => RepresentativeTransfer::class,
                'reference_id' => $transfer->id,
                'notes' => $data['notes'] ?? 'تحويل مخزون بين مندوبين',
                'status' => 'posted',
                'created_by' => $user->id,
                'approved_by' => $user->id,
            ]);

            foreach ($data['items'] as $item) {
                $qty = (float) ($item['quantity'] ?? 0);
                $baseQty = (float) ($item['base_quantity'] ?? $qty);
                if ($qty <= 0 || $baseQty <= 0) continue;

                $available = $this->available($companyId, $fromId, (int) $item['item_id']);
                if ($available < $baseQty) {
                    throw ValidationException::withMessages([
                        'items' => "الرصيد غير كاف للصنف {$item['item_id']}: المتاح {$available}، المطلوب {$baseQty}.",
                    ]);
                }

                $unitId = $item['unit_id']
                    ?? DB::table('items')->where('id', $item['item_id'])->value('base_unit_id')
                    ?? DB::table('units')->where('company_id', $companyId)->value('id')
                    ?? DB::table('units')->value('id');
                if (!$unitId) {
                    throw ValidationException::withMessages(['items' => "لا توجد وحدات معرفة في النظام للصنف {$item['item_id']}."]);
                }

                $transfer->items()->create([
                    'item_id' => $item['item_id'], 'unit_id' => $unitId,
                    'quantity' => $qty, 'base_quantity' => $baseQty,
                    'unit_cost' => $item['unit_cost'] ?? 0,
                    'batch_no' => $item['batch_no'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                ]);

                InventoryTransactionItem::create([
                    'inventory_transaction_id' => $transaction->id,
                    'item_id' => $item['item_id'], 'unit_id' => $unitId,
                    'conversion_factor' => $baseQty / $qty, 'qty' => -$baseQty,
                    'unit_cost' => $item['unit_cost'] ?? 0,
                    'total_cost' => $baseQty * (float) ($item['unit_cost'] ?? 0),
                    'batch_no' => $item['batch_no'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                    'from_location_type' => 'rep', 'from_location_id' => $fromId,
                    'to_location_type' => 'rep', 'to_location_id' => $toId,
                ]);

                $this->consumeDistribution($companyId, $fromId, (int) $item['item_id'], $baseQty);
                RepItemDistribution::create([
                    'company_id' => $companyId, 'user_id' => $toId,
                    'employee_id' => $toId,
                    'item_id' => $item['item_id'], 'loaded_qty' => $baseQty,
                    'sold_qty' => 0, 'returned_qty' => 0, 'remaining_qty' => $baseQty,
                    'unit_price' => $item['unit_cost'] ?? 0, 'status' => 'active',
                ]);
            }

            return $transfer->load(['items.item', 'fromEmployee', 'toEmployee']);
        });
    }

    private function available(int $companyId, int $employeeId, int $itemId): float
    {
        return (float) RepItemDistribution::where('company_id', $companyId)
            ->where('user_id', $employeeId)->where('item_id', $itemId)
            ->where('status', 'active')->lockForUpdate()->sum('remaining_qty');
    }

    private function consumeDistribution(int $companyId, int $employeeId, int $itemId, float $qty): void
    {
        $rows = RepItemDistribution::where('company_id', $companyId)->where('user_id', $employeeId)
            ->where('item_id', $itemId)->where('status', 'active')->where('remaining_qty', '>', 0)
            ->orderBy('id')->lockForUpdate()->get();
        foreach ($rows as $row) {
            $take = min((float) $row->remaining_qty, $qty);
            $row->decrement('remaining_qty', $take);
            $qty -= $take;
            if ($qty <= 0) break;
        }
    }

    private function nextNumber(int $companyId): string
    {
        $last = RepresentativeTransfer::withTrashed()->where('company_id', $companyId)->latest('id')->value('transfer_no');
        preg_match('/(\d+)$/', (string) $last, $match);
        return 'RPT-' . str_pad((string) (((int) ($match[1] ?? 0)) + 1), 5, '0', STR_PAD_LEFT);
    }

    private function employeeWarehouse(int $employeeId): ?int
    {
        return DB::table('salesman_assignments')->where('user_id', $employeeId)
            ->where('is_active', true)->value('warehouse_id');
    }
}
