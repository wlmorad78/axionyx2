<?php

namespace App\Services;

use App\Models\ReturnOrderSettlement;
use App\Models\ReturnOrderSettlementItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReturnOrderSettlementService
{
    public function createSettlement(array $data): ReturnOrderSettlement
    {
        return DB::transaction(function () use ($data) {
            $loadRequestNo = $data['load_request_no'] ?? null;
            if (empty($loadRequestNo) && !empty($data['load_request_id'])) {
                $loadRequest = \App\Models\LoadRequest::find($data['load_request_id']);
                if ($loadRequest) {
                    $loadRequestNo = $loadRequest->request_no;
                }
            }

            $settlement = ReturnOrderSettlement::create([
                'return_order_id' => $data['return_order_id'] ?? null,
                'user_id' => $data['user_id'],
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'load_request_no' => $loadRequestNo,
                'notes' => $data['notes'] ?? null,
            ]);

            if (!empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    $this->addItemToSettlement($settlement, $itemData);
                }
            }

            $this->recalculateTotals($settlement);

            Log::info("Return order settlement created: {$settlement->settlement_no}");

            return $settlement;
        });
    }

    public function addItemToSettlement(ReturnOrderSettlement $settlement, array $itemData): ReturnOrderSettlementItem
    {
        $soldQty = (float) ($itemData['sold_quantity'] ?? 0);
        $returnedQty = (float) ($itemData['returned_quantity'] ?? 0);
        $receivedQty = (float) ($itemData['received_quantity'] ?? 0);
        $unitPrice = (float) ($itemData['unit_price'] ?? 0);

        $expected = $soldQty + $returnedQty;
        $difference = $expected - $receivedQty;
        $financialDifference = $difference * $unitPrice;

        if ($difference > 0) {
            $type = 'debt';
        } elseif ($difference < 0) {
            $type = 'credit';
        } else {
            $type = 'balanced';
        }

        $item = $settlement->items()->create([
            'item_id' => $itemData['item_id'],
            'unit_id' => $itemData['unit_id'] ?? null,
            'loaded_quantity' => $itemData['loaded_quantity'] ?? 0,
            'sold_quantity' => $soldQty,
            'returned_quantity' => $returnedQty,
            'received_quantity' => $receivedQty,
            'difference' => $difference,
            'unit_price' => $unitPrice,
            'financial_difference' => $financialDifference,
            'type' => $type,
            'replacement_item_id' => $itemData['replacement_item_id'] ?? null,
            'replacement_quantity' => $itemData['replacement_quantity'] ?? null,
        ]);

        if (!empty($itemData['replacements']) && is_array($itemData['replacements'])) {
            foreach ($itemData['replacements'] as $replacement) {
                $item->replacements()->create([
                    'original_item_id' => $replacement['original_item_id'],
                    'replacement_item_id' => $replacement['replacement_item_id'],
                    'quantity' => $replacement['quantity'] ?? 0,
                    'unit_price' => $replacement['unit_price'] ?? 0,
                ]);
            }
        }

        return $item;
    }

    public function recalculateTotals(ReturnOrderSettlement $settlement): void
    {
        $items = $settlement->items;

        $settlement->update([
            'total_loaded' => $items->sum('loaded_quantity'),
            'total_sold' => $items->sum('sold_quantity'),
            'total_returned' => $items->sum('returned_quantity'),
            'total_received' => $items->sum('received_quantity'),
            'total_difference' => $items->sum('difference'),
            'total_financial_difference' => $items->sum('financial_difference'),
            'total_debt' => $items->where('type', 'debt')->sum('financial_difference'),
            'total_credit' => $items->where('type', 'credit')->sum('financial_difference')->abs(),
        ]);
    }

    public function approveSettlement(ReturnOrderSettlement $settlement, ?int $approvedBy = null): ReturnOrderSettlement
    {
        $settlement->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);

        Log::info("Return order settlement approved: {$settlement->settlement_no}");

        return $settlement;
    }

    public function cancelSettlement(ReturnOrderSettlement $settlement): ReturnOrderSettlement
    {
        $settlement->update([
            'status' => 'cancelled',
            'approved_by' => null,
            'approved_at' => null,
        ]);

        Log::info("Return order settlement cancelled: {$settlement->settlement_no}");

        return $settlement;
    }
}
