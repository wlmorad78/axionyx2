<?php

namespace App\Services;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\JournalEntryType;
use App\Models\Account;
use Illuminate\Support\Facades\DB;

class JournalEntryService
{
    /**
     * Create a journal entry for a sales invoice.
     *
     * Mapping:
     *   Debit:  Accounts Receivable (1003-{company_id})
     *   Credit: Sales Revenue (4001-{company_id})
     *   Credit: VAT Payable (2002-{company_id}) if tax exists
     */
    public static function createForSalesInvoice($invoice): ?JournalEntry
    {
        $companyId = $invoice->company_id;
        $branchId  = $invoice->branch_id;

        $arAccount    = self::findAccount($companyId, '1003');
        $salesAccount = self::findAccount($companyId, '4001');
        $taxAccount   = self::findAccount($companyId, '2002');

        if (!$arAccount || !$salesAccount) {
            return null;
        }

        $type = self::getOrCreateType('SI', 'فواتير المبيعات', true);

        return DB::transaction(function () use ($companyId, $branchId, $type, $invoice, $arAccount, $salesAccount, $taxAccount) {
            $taxAmount = (float) ($invoice->tax_total ?? 0);
            $netTotal  = (float) $invoice->net_total;

            $entry = JournalEntry::create([
                'company_id'            => $companyId,
                'branch_id'             => $branchId,
                'journal_entry_type_id' => $type->id,
                'entry_date'            => $invoice->invoice_date,
                'reference_type'        => 'App\Models\SalesInvoice',
                'reference_id'          => $invoice->id,
                'description'           => "قيد فاتورة مبيعات رقم {$invoice->invoice_no}",
                'total_debit'           => $netTotal,
                'total_credit'          => $netTotal,
                'status'                => 'posted',
                'created_by'            => $invoice->created_by ?? null,
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id'       => $arAccount->id,
                'description'      => 'مدين - ' . ($invoice->customer?->name ?? 'عميل'),
                'debit'            => $netTotal,
                'credit'           => 0,
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id'       => $salesAccount->id,
                'description'      => 'دائن - إيراد مبيعات',
                'debit'            => 0,
                'credit'           => $netTotal - $taxAmount,
            ]);

            if ($taxAmount > 0 && $taxAccount) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id'       => $taxAccount->id,
                    'description'      => 'دائن - ضريبة القيمة المضافة',
                    'debit'            => 0,
                    'credit'           => $taxAmount,
                ]);
            }

            self::updateAccountBalance($arAccount, $netTotal, 0);
            self::updateAccountBalance($salesAccount, 0, $netTotal - $taxAmount);
            if ($taxAmount > 0 && $taxAccount) {
                self::updateAccountBalance($taxAccount, 0, $taxAmount);
            }

            return $entry;
        });
    }

    /**
     * Create a journal entry for a purchase invoice.
     *
     * Mapping:
     *   Debit:  Cost of Goods Sold (5001-{company_id})
     *   Debit:  VAT Payable (2002-{company_id}) if tax exists
     *   Credit: Accounts Payable (2001-{company_id})
     */
    public static function createForPurchaseInvoice($invoice): ?JournalEntry
    {
        $companyId = $invoice->company_id;
        $branchId  = $invoice->branch_id;

        $purchasesAccount = self::findAccount($companyId, '5001');
        $vatAccount       = self::findAccount($companyId, '2002');
        $apAccount        = self::findAccount($companyId, '2001');

        if (!$purchasesAccount || !$apAccount) {
            return null;
        }

        $type = self::getOrCreateType('PI', 'فواتير المشتريات', true);

        return DB::transaction(function () use ($companyId, $branchId, $type, $invoice, $purchasesAccount, $vatAccount, $apAccount) {
            $taxAmount = (float) ($invoice->tax_total ?? 0);
            $netTotal  = (float) $invoice->net_total;

            $entry = JournalEntry::create([
                'company_id'            => $companyId,
                'branch_id'             => $branchId,
                'journal_entry_type_id' => $type->id,
                'entry_date'            => $invoice->invoice_date,
                'reference_type'        => 'App\Models\PurchaseInvoice',
                'reference_id'          => $invoice->id,
                'description'           => "قيد فاتورة مشتريات رقم {$invoice->invoice_no}",
                'total_debit'           => $netTotal,
                'total_credit'          => $netTotal,
                'status'                => 'posted',
                'created_by'            => $invoice->created_by ?? null,
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id'       => $purchasesAccount->id,
                'description'      => 'مدين - مشتريات',
                'debit'            => $netTotal - $taxAmount,
                'credit'           => 0,
            ]);

            if ($taxAmount > 0 && $vatAccount) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id'       => $vatAccount->id,
                    'description'      => 'مدين - ضريبة القيمة المضافة (مدفوعة مقدماً)',
                    'debit'            => $taxAmount,
                    'credit'           => 0,
                ]);
            }

            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id'       => $apAccount->id,
                'description'      => 'دائن - ' . ($invoice->supplier?->name ?? 'مورد'),
                'debit'            => 0,
                'credit'           => $netTotal,
            ]);

            self::updateAccountBalance($purchasesAccount, $netTotal - $taxAmount, 0);
            if ($taxAmount > 0 && $vatAccount) {
                self::updateAccountBalance($vatAccount, $taxAmount, 0);
            }
            self::updateAccountBalance($apAccount, 0, $netTotal);

            return $entry;
        });
    }

    /**
     * Find account by company and code prefix (matches {code}-{company_id} pattern).
     */
    private static function findAccount($companyId, $code): ?Account
    {
        return Account::where('company_id', $companyId)
            ->where('account_code', $code . '-' . $companyId)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Get or create journal entry type.
     */
    private static function getOrCreateType(string $code, string $name, bool $isSystem): JournalEntryType
    {
        return JournalEntryType::firstOrCreate(
            ['code' => $code],
            ['name' => $name, 'is_system' => $isSystem]
        );
    }

    /**
     * Update account current balance atomically.
     */
    private static function updateAccountBalance(?Account $account, float $debit, float $credit): void
    {
        if (!$account) return;

        $account->update([
            'current_balance' => DB::raw("COALESCE(current_balance, 0) + {$debit} - {$credit}"),
        ]);
    }

    /**
     * Reverse a journal entry for a deleted source document.
     */
    public static function reverseForSource(
        string $referenceType,
        int $referenceId,
        int $companyId,
        ?int $createdBy,
        string $description
    ): void {
        $entries = JournalEntry::where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->where('status', 'posted')
            ->get();

        foreach ($entries as $entry) {
            DB::transaction(function () use ($entry, $referenceType, $referenceId, $companyId, $createdBy, $description) {
                $newEntry = JournalEntry::create([
                    'company_id'            => $companyId,
                    'branch_id'             => $entry->branch_id,
                    'journal_entry_type_id' => $entry->journal_entry_type_id,
                    'entry_date'            => now()->toDateString(),
                    'reference_type'        => $referenceType,
                    'reference_id'          => $referenceId,
                    'description'           => $description,
                    'total_debit'           => $entry->total_debit,
                    'total_credit'          => $entry->total_credit,
                    'status'                => 'posted',
                    'created_by'            => $createdBy,
                ]);

                foreach ($entry->lines as $line) {
                    JournalEntryLine::create([
                        'journal_entry_id' => $newEntry->id,
                        'account_id'       => $line->account_id,
                        'description'      => 'عكس - ' . $line->description,
                        'debit'            => $line->credit,
                        'credit'           => $line->debit,
                    ]);

                    self::updateAccountBalance(
                        Account::find($line->account_id),
                        (float) $line->credit,
                        (float) $line->debit
                    );
                }

                $entry->update(['status' => 'reversed']);
            });
        }
    }
}
