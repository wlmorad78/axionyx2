<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PopulateCustomerLedgerSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('customer_ledger')->truncate();

        $invoices = DB::table('sales_invoices')
            ->where('status', '!=', 'cancelled')
            ->whereNull('deleted_at')
            ->orderBy('customer_id')
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get();

        $customerBalances = [];

        foreach ($invoices as $invoice) {
            $customerId = (int) $invoice->customer_id;

            $previousBalance = $customerBalances[$customerId] ?? 0;
            $runningBalance = $previousBalance + (float) $invoice->net_total - (float) $invoice->paid_amount;

            DB::table('customer_ledger')->insert([
                'customer_id' => $customerId,
                'transaction_date' => $invoice->invoice_date ?? $invoice->created_at,
                'reference_type' => 'invoice',
                'reference_id' => $invoice->id,
                'debit' => $invoice->net_total,
                'credit' => $invoice->paid_amount,
                'balance' => round($runningBalance, 2),
                'created_at' => $invoice->created_at ?? now(),
                'updated_at' => $invoice->created_at ?? now(),
            ]);

            $customerBalances[$customerId] = $runningBalance;
        }

        $this->command->info("Customer ledger populated for " . $invoices->count() . " invoices.");
    }
}
