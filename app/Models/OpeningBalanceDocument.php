<?php
namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToCompany;
use App\Services\Document;
use Illuminate\Support\Facades\DB;

class OpeningBalanceDocument extends Document {
    use SoftDeletes, BelongsToCompany;

    protected $table = 'opening_balance_documents';

    protected $fillable = [
        'company_id','branch_id','document_no','document_date','status',
        'balance_type','notes','created_by','posted_by','posted_at',
    ];
    protected $casts = ['document_date'=>'date','posted_at'=>'datetime'];

    public function company() { return $this->belongsTo(Company::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
    public function postedBy() { return $this->belongsTo(User::class, 'posted_by'); }
    public function lines() { return $this->hasMany(OpeningBalanceDocumentLine::class); }

    protected function documentType(): string { return 'opening_balance'; }
    protected function numberField(): string { return 'document_no'; }

    protected function validateBusinessRules(): void
    {
        if ($this->lines()->count() === 0) {
            throw new \DomainException('يجب إضافة سطر واحد على الأقل');
        }

        if ($this->balance_type === 'inventory') {
            $invalidLines = $this->lines()
                ->whereNull('item_id')
                ->orWhereNull('warehouse_id')
                ->orWhere('qty', '<=', 0)
                ->count();
            if ($invalidLines > 0) {
                throw new \DomainException('يجب تحديد الصنف والمخزون والكمية لكل سطر');
            }
        }
    }

    protected function onApprove(): void
    {
        $this->update(['posted_by' => \Auth::id(), 'posted_at' => now()]);
    }

    protected function onPost(): void
    {
        $this->applyPost();
    }

    protected function onCancel(): void
    {
        $this->reversePost();
        $this->update(['posted_by' => null, 'posted_at' => null]);
    }

    protected function onReopen(): void {}

    private function applyPost(): void
    {
        $lines = $this->lines()->get();

        // 1. Create Journal Entry
        $totalDebit = $lines->sum('debit');
        $totalCredit = $lines->sum('credit');

        if ($totalDebit > 0 || $totalCredit > 0) {
            $journalEntry = JournalEntry::create([
                'company_id' => $this->company_id,
                'branch_id' => $this->branch_id,
                'journal_entry_type_id' => null,
                'entry_date' => $this->document_date,
                'reference_type' => OpeningBalanceDocument::class,
                'reference_id' => $this->id,
                'description' => 'Opening Balance - ' . $this->document_no,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'status' => 'posted',
                'created_by' => $this->posted_by ?? $this->created_by,
            ]);

            foreach ($lines as $line) {
                if ($line->account_id && ($line->debit > 0 || $line->credit > 0)) {
                    JournalEntryLine::create([
                        'journal_entry_id' => $journalEntry->id,
                        'account_id' => $line->account_id,
                        'description' => $line->description,
                        'debit' => $line->debit,
                        'credit' => $line->credit,
                    ]);
                }
            }
        }

        // 2. Create Inventory Transactions for item lines
        $itemLines = $lines->where('item_id', '!=', null)->where('warehouse_id', '!=', null);
        if ($itemLines->isNotEmpty()) {
            $transactionTypes = InventoryTransactionType::all();
            $obType = $transactionTypes->firstWhere('code', 'OPENING_BALANCE')
                ?? $transactionTypes->first(fn($t) => str_contains($t->name_ar ?? '', 'افتتاحية'))
                ?? $transactionTypes->first();

            $invTransaction = InventoryTransaction::create([
                'company_id' => $this->company_id,
                'branch_id' => $this->branch_id,
                'warehouse_id' => $itemLines->first()->warehouse_id,
                'inventory_transaction_type_id' => $obType?->id,
                'transaction_no' => 'OB-' . $this->document_no,
                'transaction_time' => $this->document_date,
                'reference_type' => OpeningBalanceDocument::class,
                'reference_id' => $this->id,
                'notes' => 'Opening Balance',
                'status' => 'posted',
                'created_by' => $this->posted_by ?? $this->created_by,
            ]);

            $unitService = app(\App\Services\UnitConversionService::class);

            foreach ($itemLines as $line) {
                $conversionFactor = 1;
                if (!empty($line->unit_id)) {
                    $iu = \App\Models\ItemUnit::where('item_id', $line->item_id)
                        ->where('unit_id', $line->unit_id)
                        ->whereNull('deleted_at')
                        ->first();
                    if ($iu && $iu->conversion_factor > 0) $conversionFactor = $iu->conversion_factor;
                }
                $qtyInBase = (float)$line->qty * $conversionFactor;

                InventoryTransactionItem::create([
                    'inventory_transaction_id' => $invTransaction->id,
                    'item_id' => $line->item_id,
                    'unit_id' => $line->unit_id,
                    'warehouse_id' => $line->warehouse_id,
                    'conversion_factor' => $conversionFactor,
                    'qty' => $qtyInBase,
                    'unit_cost' => $line->unit_cost,
                    'total_cost' => $qtyInBase * $line->unit_cost,
                    'direction' => 'in',
                ]);
            }
        }

        // 3. Update customer opening_balance
        $customerLines = $lines->where('customer_id', '!=', null);
        foreach ($customerLines as $line) {
            $balance = $line->debit - $line->credit;
            $line->customer->update(['opening_balance' => $balance]);
        }

        // 4. Update supplier opening_balance
        $supplierLines = $lines->where('supplier_id', '!=', null);
        foreach ($supplierLines as $line) {
            $balance = $line->credit - $line->debit;
            $line->supplier->update(['opening_balance' => $balance]);
        }

        // 5. Update treasury opening_balance
        $treasuryLines = $lines->where('account_id', '!=', null);
        foreach ($treasuryLines as $line) {
            if ($line->account && str_starts_with($line->account->code ?? '', '1001')) {
                $treasury = Treasury::where('account_id', $line->account_id)->first();
                if ($treasury) {
                    $treasury->update(['opening_balance' => $line->debit - $line->credit]);
                }
            }
        }
    }

    private function reversePost(): void
    {
        // Reverse Journal Entry
        JournalEntry::where('reference_type', OpeningBalanceDocument::class)
            ->where('reference_id', $this->id)
            ->each(function ($je) {
                $je->lines()->delete();
                $je->delete();
            });

        // Reverse Inventory Transactions
        InventoryTransaction::where('reference_type', OpeningBalanceDocument::class)
            ->where('reference_id', $this->id)
            ->each(function ($it) {
                $it->items()->delete();
                $it->delete();
            });

        // Reset customer balances
        $customerLines = $this->lines()->where('customer_id', '!=', null)->get();
        foreach ($customerLines as $line) {
            $line->customer?->update(['opening_balance' => 0]);
        }

        // Reset supplier balances
        $supplierLines = $this->lines()->where('supplier_id', '!=', null)->get();
        foreach ($supplierLines as $line) {
            $line->supplier?->update(['opening_balance' => 0]);
        }

        // Reset treasury balances
        $treasuryLines = $this->lines()->where('account_id', '!=', null)->get();
        foreach ($treasuryLines as $line) {
            if ($line->account && str_starts_with($line->account->code ?? '', '1001')) {
                $treasury = Treasury::where('account_id', $line->account_id)->first();
                if ($treasury) {
                    $treasury->update(['opening_balance' => 0]);
                }
            }
        }
    }

    protected static function booted(): void {
        static::creating(function (OpeningBalanceDocument $model) {
            if (!$model->document_no) {
                $model->document_no = $model->generateNumber();
            }
        });
    }
}
