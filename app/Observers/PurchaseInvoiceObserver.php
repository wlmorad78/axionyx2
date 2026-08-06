<?php

namespace App\Observers;

use App\Models\PurchaseInvoice;
use App\Models\JournalEntry;
use App\Services\JournalEntryService;

class PurchaseInvoiceObserver
{
    /**
     * Handle the PurchaseInvoice "created" event.
     * Auto-generate journal entry when invoice is created.
     */
    public function created(PurchaseInvoice $invoice): void
    {
        try {
            JournalEntryService::createForPurchaseInvoice($invoice);
        } catch (\Exception $e) {
            \Log::error("Failed to create journal entry for purchase invoice {$invoice->invoice_no}: " . $e->getMessage());
        }
    }

    /**
     * Handle the PurchaseInvoice "deleted" event.
     * Auto-reverse journal entry when invoice is deleted.
     */
    public function deleted(PurchaseInvoice $invoice): void
    {
        try {
            JournalEntryService::reverseForSource(
                PurchaseInvoice::class,
                $invoice->id,
                $invoice->company_id,
                $invoice->created_by,
                "عكس قيد فاتورة مشتريات محذوفة رقم {$invoice->invoice_no}"
            );
        } catch (\Exception $e) {
            \Log::error("Failed to reverse journal entry for purchase invoice {$invoice->invoice_no}: " . $e->getMessage());
        }
    }
}
