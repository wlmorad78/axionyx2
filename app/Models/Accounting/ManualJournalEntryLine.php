<?php
namespace App\Models\Accounting;
use Illuminate\Database\Eloquent\Model;

class ManualJournalEntryLine extends Model {
    protected $fillable = ['manual_journal_entry_id','account_id','cost_center_id','debit','credit','description'];
    protected $casts = ['debit'=>'decimal:2','credit'=>'decimal:2'];
    public function manualJournalEntry() { return $this->belongsTo(ManualJournalEntry::class); }
    public function account() { return $this->belongsTo(Account::class); }
    public function costCenter() { return $this->belongsTo(CostCenter::class); }
}
