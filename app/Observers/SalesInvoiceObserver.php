<?php

namespace App\Observers;

use App\Models\SalesInvoice;
use App\Models\JournalEntry;
use App\Services\JournalEntryService;

class SalesInvoiceObserver
{
    /**
     * Handle the SalesInvoice "created" event.
     * Auto-generate journal entry when invoice is created.
     */
    public function created(SalesInvoice $invoice): void
    {
        try {
            JournalEntryService::createForSalesInvoice($invoice);
        } catch (\Exception $e) {
            \Log::error("Failed to create journal entry for sales invoice {$invoice->invoice_no}: " . $e->getMessage());
        }
    }

    /**
     * Handle the SalesInvoice "deleted" event.
     * Auto-reverse journal entry when invoice is deleted.
     */
    public function deleted(SalesInvoice $invoice): void
    {
        try {
            JournalEntryService::reverseForSource(
                SalesInvoice::class,
                $invoice->id,
                $invoice->company_id,
                $invoice->created_by,
                "عكس قيد فاتورة مبيعات محذوفة رقم {$invoice->invoice_no}"
            );
        } catch (\Exception $e) {
            \Log::error("Failed to reverse journal entry for sales invoice {$invoice->invoice_no}: " . $e->getMessage());
        }
    }
}
