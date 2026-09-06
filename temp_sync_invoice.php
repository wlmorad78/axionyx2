<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SalesInvoice;
use Illuminate\Support\Facades\Http;

$inv = SalesInvoice::with(['items.item', 'items.unit', 'customer', 'branch', 'warehouse', 'company'])->find(1599);

if (!$inv) {
    echo "Invoice 1599 not found\n";
    exit(1);
}

$payload = [
    'uuid'                      => $inv->uuid,
    'invoice_no'                => $inv->invoice_no,
    'invoice_date'              => $inv->invoice_date,
    'invoice_time'              => $inv->invoice_time,
    'customer_id'               => $inv->customer_id,
    'sales_rep_id'              => $inv->sales_rep_id,
    'warehouse_id'              => $inv->warehouse_id,
    'branch_id'                 => $inv->branch_id,
    'company_id'                => $inv->company_id,
    'route_id'                  => $inv->route_id,
    'sales_territory_id'        => $inv->sales_territory_id,
    'payment_term_id'           => $inv->payment_term_id,
    'currency_id'               => $inv->currency_id,
    'exchange_rate'             => $inv->exchange_rate,
    'subtotal'                  => $inv->subtotal,
    'item_discount_total'       => $inv->item_discount_total,
    'invoice_discount_total'    => $inv->invoice_discount_total,
    'tax_total'                 => $inv->tax_total,
    'incentive_total'           => $inv->incentive_total,
    'net_total'                 => $inv->net_total,
    'paid_amount'               => $inv->paid_amount,
    'remaining_amount'          => $inv->remaining_amount,
    'status'                    => $inv->status,
    'notes'                     => $inv->notes,
    'source'                    => $inv->source,
    'mode'                      => $inv->mode,
    'device_id'                 => $inv->device_id,
    'created_by'                => $inv->created_by,
    'approved_by'               => $inv->approved_by,
    'posted_at'                 => is_object($inv->posted_at) && method_exists($inv->posted_at, 'toISOString') ? $inv->posted_at->toISOString() : $inv->posted_at,
    'items'                     => $inv->items->map(function ($item) {
        return [
            'item_id'           => $item->item_id,
            'unit_id'           => $item->unit_id,
            'warehouse_id'      => $item->warehouse_id,
            'qty'               => $item->qty,
            'bonus_qty'         => $item->bonus_qty,
            'conversion_factor' => $item->conversion_factor,
            'base_quantity'     => $item->base_quantity,
            'price'             => $item->price,
            'gross_amount'      => $item->gross_amount,
            'discount_type'     => $item->discount_type,
            'discount_value'    => $item->discount_value,
            'discount_amount'   => $item->discount_amount,
            'tax_id'            => $item->tax_id,
            'tax_percent'       => $item->tax_percent,
            'tax_amount'        => $item->tax_amount,
            'net_amount'        => $item->net_amount,
            'notes'             => $item->notes,
        ];
    })->toArray(),
];

$syncToken = env('SYNC_TOKEN', 'your_secret_sync_token_here');
$externalUrl = env('SYNC_EXTERNAL_URL', 'http://207.231.110.79');

echo "Sending invoice {$inv->invoice_no} to {$externalUrl}...\n";

$response = Http::withHeaders([
    'X-Sync-Token' => $syncToken,
    'Accept'       => 'application/json',
    'Content-Type' => 'application/json',
])->timeout(60)->post($externalUrl . '/api/v2/sync/receive-invoice', $payload);

echo "Status: {$response->status()}\n";
echo "Response: {$response->body()}\n";
