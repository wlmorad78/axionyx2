<?php

namespace App\Modules\Sync\Controllers;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\SalesInvoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncController extends BaseApiController
{
    /**
     * POST /api/v2/sync/send-invoice
     * Push a sales invoice from local server to external server.
     *
     * Body: { "invoice_id": 123 }
     * Header: X-Sync-Token: <secret shared between servers>
     */
    public function sendInvoice(Request $request): JsonResponse
    {
        $request->validate([
            'invoice_id' => 'required|integer|exists:sales_invoices,id',
        ]);

        // 1. Fetch invoice with all relations
        $invoice = SalesInvoice::with([
            'items.item',
            'items.unit',
            'company',
            'branch',
            'warehouse',
            'customer',
        ])->findOrFail($request->invoice_id);

        // 2. Build payload
        $payload = $this->buildInvoicePayload($invoice);

        // 3. Push to external server
        $externalUrl = config('sync.external_server_url');
        $syncToken  = config('sync.sync_token');

        if (!$externalUrl || !$syncToken) {
            return $this->errorResponse('External server not configured. Check config/sync.php', 500);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $syncToken,
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ])->timeout(60)->post($externalUrl . '/api/v2/sync/receive-invoice', $payload);

            if ($response->successful()) {
                $invoice->update([
                    'sync_status' => 'synced',
                    'synced_at'   => now(),
                ]);

                return $this->successResponse([
                    'message'  => 'Invoice synced successfully',
                    'invoice'  => $invoice->invoice_no,
                    'response' => $response->json(),
                ]);
            }

            Log::error('Sync failed', [
                'invoice_id' => $invoice->id,
                'status'     => $response->status(),
                'body'       => $response->body(),
            ]);

            return $this->errorResponse('Sync failed: ' . $response->body(), $response->status());
        } catch (\Exception $e) {
            Log::error('Sync exception', [
                'invoice_id' => $invoice->id,
                'message'    => $e->getMessage(),
            ]);

            return $this->errorResponse('Connection error: ' . $e->getMessage(), 502);
        }
    }

    /**
     * POST /api/v2/sync/receive-invoice
     * Receive a sales invoice from another server.
     *
     * Protected by X-Sync-Token header.
     */
    public function receiveInvoice(Request $request): JsonResponse
    {
        // 1. Validate sync token
        $token = $request->header('X-Sync-Token');
        if ($token !== config('sync.sync_token')) {
            return $this->errorResponse('Invalid sync token', 401);
        }

        $data = $request->all();

        // 2. Validate required fields
        if (empty($data['invoice_no'])) {
            return $this->errorResponse('invoice_no is required', 422);
        }

        // 3. Check if invoice already exists (by uuid or invoice_no)
        $exists = SalesInvoice::where('invoice_no', $data['invoice_no'])
            ->orWhere('uuid', $data['uuid'] ?? '')
            ->exists();

        if ($exists) {
            return $this->errorResponse('Invoice already exists', 409);
        }

        // 4. Insert invoice + items in a transaction
        try {
            $invoice = DB::transaction(function () use ($data) {
                $invoice = SalesInvoice::create([
                    'uuid'               => $data['uuid'],
                    'invoice_no'         => $data['invoice_no'],
                    'invoice_date'       => $data['invoice_date'],
                    'invoice_time'       => $data['invoice_time'] ?? null,
                    'customer_id'        => $data['customer_id'],
                    'sales_rep_id'       => $data['sales_rep_id'] ?? null,
                    'warehouse_id'       => $data['warehouse_id'] ?? null,
                    'branch_id'          => $data['branch_id'] ?? null,
                    'company_id'         => $data['company_id'] ?? null,
                    'route_id'           => $data['route_id'] ?? null,
                    'subtotal'           => $data['subtotal'] ?? 0,
                    'item_discount_total' => $data['item_discount_total'] ?? 0,
                    'invoice_discount_total' => $data['invoice_discount_total'] ?? 0,
                    'tax_total'          => $data['tax_total'] ?? 0,
                    'net_total'          => $data['net_total'] ?? 0,
                    'paid_amount'        => $data['paid_amount'] ?? 0,
                    'remaining_amount'   => $data['remaining_amount'] ?? 0,
                    'status'             => $data['status'] ?? 'draft',
                    'notes'              => $data['notes'] ?? null,
                    'sync_status'        => 'synced',
                    'synced_at'          => now(),
                    'source'             => 'sync',
                    'created_by'         => $data['created_by'] ?? null,
                ]);

                if (!empty($data['items']) && is_array($data['items'])) {
                    foreach ($data['items'] as $item) {
                        $invoice->items()->create([
                            'item_id'          => $item['item_id'],
                            'unit_id'          => $item['unit_id'] ?? null,
                            'warehouse_id'     => $item['warehouse_id'] ?? null,
                            'qty'              => $item['qty'] ?? 0,
                            'bonus_qty'        => $item['bonus_qty'] ?? 0,
                            'price'            => $item['price'] ?? 0,
                            'gross_amount'     => $item['gross_amount'] ?? 0,
                            'discount_type'    => $item['discount_type'] ?? null,
                            'discount_value'   => $item['discount_value'] ?? 0,
                            'discount_amount'  => $item['discount_amount'] ?? 0,
                            'tax_id'           => $item['tax_id'] ?? null,
                            'tax_percent'      => $item['tax_percent'] ?? 0,
                            'tax_amount'       => $item['tax_amount'] ?? 0,
                            'net_amount'       => $item['net_amount'] ?? 0,
                            'notes'            => $item['notes'] ?? null,
                        ]);
                    }
                }

                return $invoice;
            });

            Log::info('Invoice received via sync', [
                'invoice_id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
            ]);

            return $this->successResponse([
                'message'    => 'Invoice received successfully',
                'invoice_id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Receive invoice failed', [
                'invoice_no' => $data['invoice_no'] ?? null,
                'message'    => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to save invoice: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v2/sync/pull-invoice
     * Pull a sales invoice from external server to local.
     *
     * Body: { "invoice_no": "INV-001" }
     */
    public function pullInvoice(Request $request): JsonResponse
    {
        $request->validate([
            'invoice_no' => 'required|string',
        ]);

        $externalUrl = config('sync.external_server_url');
        $syncToken  = config('sync.sync_token');

        if (!$externalUrl || !$syncToken) {
            return $this->errorResponse('External server not configured', 500);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $syncToken,
                'Accept'        => 'application/json',
            ])->timeout(60)->get($externalUrl . '/api/v2/sync/export-invoice', [
                'invoice_no' => $request->invoice_no,
            ]);

            if ($response->failed()) {
                return $this->errorResponse('Failed to pull: ' . $response->body(), $response->status());
            }

            $data = $response->json('data');

            // Check if exists locally
            $exists = SalesInvoice::where('invoice_no', $data['invoice_no'])->exists();
            if ($exists) {
                return $this->errorResponse('Invoice already exists locally', 409);
            }

            // Save locally
            $invoice = DB::transaction(function () use ($data) {
                $invoice = SalesInvoice::create([
                    'uuid'               => $data['uuid'],
                    'invoice_no'         => $data['invoice_no'],
                    'invoice_date'       => $data['invoice_date'],
                    'invoice_time'       => $data['invoice_time'] ?? null,
                    'customer_id'        => $data['customer_id'],
                    'sales_rep_id'       => $data['sales_rep_id'] ?? null,
                    'warehouse_id'       => $data['warehouse_id'] ?? null,
                    'branch_id'          => $data['branch_id'] ?? null,
                    'company_id'         => $data['company_id'] ?? null,
                    'route_id'           => $data['route_id'] ?? null,
                    'subtotal'           => $data['subtotal'] ?? 0,
                    'item_discount_total' => $data['item_discount_total'] ?? 0,
                    'invoice_discount_total' => $data['invoice_discount_total'] ?? 0,
                    'tax_total'          => $data['tax_total'] ?? 0,
                    'net_total'          => $data['net_total'] ?? 0,
                    'paid_amount'        => $data['paid_amount'] ?? 0,
                    'remaining_amount'   => $data['remaining_amount'] ?? 0,
                    'status'             => $data['status'] ?? 'draft',
                    'notes'              => $data['notes'] ?? null,
                    'sync_status'        => 'synced',
                    'synced_at'          => now(),
                    'source'             => 'sync',
                    'created_by'         => $data['created_by'] ?? null,
                ]);

                if (!empty($data['items'])) {
                    foreach ($data['items'] as $item) {
                        $invoice->items()->create([
                            'item_id'          => $item['item_id'],
                            'unit_id'          => $item['unit_id'] ?? null,
                            'warehouse_id'     => $item['warehouse_id'] ?? null,
                            'qty'              => $item['qty'] ?? 0,
                            'bonus_qty'        => $item['bonus_qty'] ?? 0,
                            'price'            => $item['price'] ?? 0,
                            'gross_amount'     => $item['gross_amount'] ?? 0,
                            'discount_type'    => $item['discount_type'] ?? null,
                            'discount_value'   => $item['discount_value'] ?? 0,
                            'discount_amount'  => $item['discount_amount'] ?? 0,
                            'tax_id'           => $item['tax_id'] ?? null,
                            'tax_percent'      => $item['tax_percent'] ?? 0,
                            'tax_amount'       => $item['tax_amount'] ?? 0,
                            'net_amount'       => $item['net_amount'] ?? 0,
                            'notes'            => $item['notes'] ?? null,
                        ]);
                    }
                }

                return $invoice;
            });

            return $this->successResponse([
                'message'    => 'Invoice pulled and saved locally',
                'invoice_id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Pull failed: ' . $e->getMessage(), 502);
        }
    }

    /**
     * GET /api/v2/sync/export-invoice
     * Export invoice data for another server to pull.
     */
    public function exportInvoice(Request $request): JsonResponse
    {
        // Validate sync token
        $token = $request->header('X-Sync-Token');
        if ($token !== config('sync.sync_token')) {
            return $this->errorResponse('Invalid sync token', 401);
        }

        $request->validate([
            'invoice_no' => 'required|string',
        ]);

        $invoice = SalesInvoice::with([
            'items.item',
            'items.unit',
        ])->where('invoice_no', $request->invoice_no)->first();

        if (!$invoice) {
            return $this->errorResponse('Invoice not found', 404);
        }

        return $this->successResponse($this->buildInvoicePayload($invoice));
    }

    protected function buildInvoicePayload(SalesInvoice $invoice): array
    {
        return [
            'uuid'                      => $invoice->uuid,
            'invoice_no'                => $invoice->invoice_no,
            'invoice_date'              => $invoice->invoice_date,
            'invoice_time'              => $invoice->invoice_time,
            'customer_id'               => $invoice->customer_id,
            'sales_rep_id'              => $invoice->sales_rep_id,
            'warehouse_id'              => $invoice->warehouse_id,
            'branch_id'                 => $invoice->branch_id,
            'company_id'                => $invoice->company_id,
            'route_id'                  => $invoice->route_id,
            'sales_territory_id'        => $invoice->sales_territory_id,
            'payment_term_id'           => $invoice->payment_term_id,
            'currency_id'               => $invoice->currency_id,
            'exchange_rate'             => $invoice->exchange_rate,
            'subtotal'                  => $invoice->subtotal,
            'item_discount_total'       => $invoice->item_discount_total,
            'invoice_discount_total'    => $invoice->invoice_discount_total,
            'tax_total'                 => $invoice->tax_total,
            'incentive_total'           => $invoice->incentive_total,
            'net_total'                 => $invoice->net_total,
            'paid_amount'               => $invoice->paid_amount,
            'remaining_amount'          => $invoice->remaining_amount,
            'status'                    => $invoice->status,
            'notes'                     => $invoice->notes,
            'source'                    => $invoice->source,
            'mode'                      => $invoice->mode,
            'device_id'                 => $invoice->device_id,
            'created_by'                => $invoice->created_by,
            'approved_by'               => $invoice->approved_by,
            'posted_at'                 => $invoice->posted_at?->toISOString(),
            'items'                     => $invoice->items->map(function ($item) {
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
    }
}
