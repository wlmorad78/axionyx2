<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixOpeningBalanceQty extends Command
{
    protected $signature = 'fix:opening-balance-qty {--dry-run : Show changes without applying them}';
    protected $description = 'Fix InventoryTransactionItem qty for OpeningBalanceDocument records that stored raw qty instead of base qty';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('Fixing OpeningBalanceDocument inventory_transaction_items...');
        $this->newLine();

        $fixedCount = 0;
        $skippedCount = 0;

        // Find all InventoryTransactionItem records linked to OpeningBalanceDocument via reference_type
        $txnItems = DB::table('inventory_transaction_items')
            ->join('inventory_transactions', 'inventory_transactions.id', '=', 'inventory_transaction_items.inventory_transaction_id')
            ->whereIn('inventory_transactions.reference_type', [
                'App\\Models\\OpeningBalanceDocument',
                'App\\Models\\Accounting\\OpeningBalanceDocument',
            ])
            ->whereNull('inventory_transaction_items.deleted_at')
            ->whereNull('inventory_transactions.deleted_at')
            ->select(
                'inventory_transaction_items.id as item_id',
                'inventory_transaction_items.item_id as product_id',
                'inventory_transaction_items.unit_id',
                'inventory_transaction_items.qty',
                'inventory_transaction_items.conversion_factor as stored_cf'
            )
            ->get();

        foreach ($txnItems as $txnItem) {
            $correctCf = 1;
            if (!empty($txnItem->unit_id)) {
                $iu = DB::table('item_units')
                    ->where('item_id', $txnItem->product_id)
                    ->where('unit_id', $txnItem->unit_id)
                    ->whereNull('deleted_at')
                    ->first();
                if ($iu && $iu->conversion_factor > 0) {
                    $correctCf = $iu->conversion_factor;
                }
            }

            // Only fix if the stored CF doesn't match the correct one
            if (abs((float)$txnItem->stored_cf - $correctCf) > 0.0001) {
                $newQty = (float)$txnItem->qty * $correctCf;
                $this->line(sprintf(
                    '  Item #%d: qty %.2f (cf=%.2f) -> %.2f (cf=%.2f)',
                    $txnItem->item_id,
                    $txnItem->qty,
                    $txnItem->stored_cf,
                    $newQty,
                    $correctCf
                ));

                if (!$dryRun) {
                    DB::table('inventory_transaction_items')
                        ->where('id', $txnItem->item_id)
                        ->update([
                            'qty' => $newQty,
                            'conversion_factor' => $correctCf,
                        ]);
                }

                $fixedCount++;
            } else {
                $skippedCount++;
            }
        }

        $this->newLine();
        $this->info("Fixed: {$fixedCount} records");
        $this->info("Skipped (already correct): {$skippedCount} records");

        if ($dryRun) {
            $this->warn('DRY RUN - no changes applied. Run without --dry-run to apply.');
        }

        return 0;
    }
}
