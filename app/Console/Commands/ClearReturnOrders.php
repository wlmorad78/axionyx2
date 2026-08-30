<?php

namespace App\Console\Commands;

use App\Models\ReturnOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearReturnOrders extends Command
{
    protected $signature = 'return-orders:clear {--company= : Filter by company_id}';
    protected $description = 'Delete all return orders and all related data (inventory, debts, invoices, movements)';

    public function handle(): int
    {
        $companyId = $this->option('company');

        $this->info('Starting return orders cleanup...');
        $this->newLine();

        DB::transaction(function () use ($companyId) {
            $returnQuery = ReturnOrder::query();
            if ($companyId) {
                $returnQuery->where('company_id', $companyId);
            }

            $returnIds = $returnQuery->pluck('id')->toArray();
            $returnNos = ReturnOrder::whereIn('id', $returnIds)->pluck('return_no')->toArray();

            if (empty($returnIds)) {
                $this->warn('No return orders found.');
                return;
            }

            $this->info("Found " . count($returnIds) . " return orders to delete.");

            // 1. rep_item_distributions
            $count = DB::table('rep_item_distributions')
                ->whereIn('return_order_id', $returnIds)
                ->update(['return_order_id' => null]);
            $this->line("  rep_item_distributions: {$count} records unlinked");

            // 2. vehicle_unloads
            $count = DB::table('vehicle_unloads')
                ->whereIn('return_order_id', $returnIds)
                ->update(['return_order_id' => null]);
            $this->line("  vehicle_unloads: {$count} records unlinked");

            // 3. inventory_transaction_items (via inventory_transactions referencing ReturnOrder)
            $txnIds = DB::table('inventory_transactions')
                ->where('reference_type', ReturnOrder::class)
                ->whereIn('reference_id', $returnIds)
                ->pluck('id');
            if ($txnIds->count()) {
                $count = DB::table('inventory_transaction_items')
                    ->whereIn('inventory_transaction_id', $txnIds)
                    ->delete();
                $this->line("  inventory_transaction_items: {$count} deleted");

                DB::table('inventory_transactions')
                    ->whereIn('id', $txnIds)
                    ->delete();
                $this->line("  inventory_transactions: {$txnIds->count()} deleted");
            }

            // 4. sales_invoice_items + sales_invoices (created from return orders)
            $invoiceIds = DB::table('sales_invoices')
                ->whereIn('notes', $returnNos)
                ->pluck('id');
            if ($invoiceIds->count()) {
                $count = DB::table('sales_invoice_items')
                    ->whereIn('sales_invoice_id', $invoiceIds)
                    ->delete();
                $this->line("  sales_invoice_items: {$count} deleted");

                DB::table('sales_invoices')
                    ->whereIn('id', $invoiceIds)
                    ->delete();
                $this->line("  sales_invoices: {$invoiceIds->count()} deleted");
            }

            // 5. salesman_debt_payment_lines + salesman_debts (created from return orders)
            $debtNotes = array_map(fn($no) => "%{$no}%", $returnNos);
            $debtQuery = DB::table('salesman_debts');
            if ($companyId) {
                $debtQuery->where('company_id', $companyId);
            }
            $debtQuery->where(function ($q) use ($debtNotes) {
                foreach ($debtNotes as $note) {
                    $q->orWhere('notes', 'like', $note);
                }
            });
            $debtIds = $debtQuery->pluck('id');

            if ($debtIds->count()) {
                $count = DB::table('salesman_debt_payment_lines')
                    ->whereIn('salesman_debt_id', $debtIds)
                    ->delete();
                $this->line("  salesman_debt_payment_lines: {$count} deleted");

                DB::table('salesman_debts')
                    ->whereIn('id', $debtIds)
                    ->delete();
                $this->line("  salesman_debts: {$debtIds->count()} deleted");
            }

            // 6. salesman_account_movements (debt_creation linked to deleted debts)
            if ($debtIds->count()) {
                $count = DB::table('salesman_account_movements')
                    ->where('reference_type', 'App\\Models\\Sales\\SalesmanDebt')
                    ->whereIn('reference_id', $debtIds)
                    ->delete();
                $this->line("  salesman_account_movements: {$count} deleted");
            }

            // 7. return_order_items
            $count = DB::table('return_order_items')
                ->whereIn('return_order_id', $returnIds)
                ->delete();
            $this->line("  return_order_items: {$count} deleted");

            // 8. return_orders (hard delete since they use SoftDeletes)
            $count = ReturnOrder::whereIn('id', $returnIds)->forceDelete();
            $this->line("  return_orders: {$count} deleted");

            $this->newLine();
            $this->info("Done. All return orders and related data deleted.");
        });

        return 0;
    }
}
