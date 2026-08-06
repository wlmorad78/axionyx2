<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $customers = DB::table('customers')->where('is_active', 1)->select('id')->get();
        $warehouse = DB::table('warehouses')->where('is_active', 1)->select('id')->first();
        $whId = $warehouse?->id;
        $salesRep = DB::table('employees')->where('email', '10002@axionyx.com')->select('id')->first();
        $salesRepId = $salesRep?->id ?? 1;

        $companyId = DB::table('companies')->select('id')->first()->id ?? 1;

        $nextInvoice = 1;
        $quantities = [
            [5, 10],
            [8, 15],
            [3, 20],
            [12, 6],
        ];

        foreach ($customers as $customer) {
            for ($month = 1; $month <= 4; $month++) {
                $date = "2025-06-" . str_pad(($month * 7), 2, '0', STR_PAD_LEFT);
                $invoiceNo = 'SI-' . str_pad($nextInvoice, 5, '0', STR_PAD_LEFT);
                $nextInvoice++;

                $qty1 = $quantities[$month - 1][0];
                $qty2 = $quantities[$month - 1][1];
                $price1 = 479.5;
                $price2 = 479.5;
                $sub1 = $qty1 * $price1;
                $sub2 = $qty2 * $price2;
                $subtotal = $sub1 + $sub2;

                $invoiceId = DB::table('sales_invoices')->insertGetId([
                    'company_id' => $companyId,
                    'warehouse_id' => $whId,
                    'invoice_no' => $invoiceNo,
                    'customer_id' => $customer->id,
                    'sales_rep_id' => $salesRepId,
                    'invoice_date' => $date,
                    'invoice_time' => '10:00:00',
                    'subtotal' => $subtotal,
                    'item_discount_total' => 0,
                    'invoice_discount_total' => 0,
                    'tax_total' => 0,
                    'incentive_total' => 0,
                    'net_total' => $subtotal,
                    'paid_amount' => $subtotal,
                    'remaining_amount' => 0,
                    'status' => 'approved',
                    'source' => 'desktop',
                    'sync_status' => 'synced',
                    'notes' => 'فاتورة تجريبية - يونيو 2025',
                    'created_by' => $salesRepId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('sales_invoice_items')->insert([
                    [
                        'sales_invoice_id' => $invoiceId,
                        'item_id' => 1,
                        'qty' => $qty1,
                        'bonus_qty' => 0,
                        'price' => $price1,
                        'gross_amount' => $sub1,
                        'discount_type' => null,
                        'discount_value' => 0,
                        'discount_amount' => 0,
                        'tax_percent' => 0,
                        'tax_amount' => 0,
                        'net_amount' => $sub1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'sales_invoice_id' => $invoiceId,
                        'item_id' => 2,
                        'qty' => $qty2,
                        'bonus_qty' => 0,
                        'price' => $price2,
                        'gross_amount' => $sub2,
                        'discount_type' => null,
                        'discount_value' => 0,
                        'discount_amount' => 0,
                        'tax_percent' => 0,
                        'tax_amount' => 0,
                        'net_amount' => $sub2,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ]);
            }
        }

        DB::table('number_series')
            ->where('document_type', 'sales_invoice')
            ->update(['next_sequence' => $nextInvoice]);
    }

    public function down(): void
    {
        //
    }
};
