<?php
namespace App\Models\Accounting;
use Illuminate\Database\Eloquent\Model;

class JournalEntryLine extends Model {
    protected $fillable = ['journal_entry_id','account_id','cost_center_id','description','debit','credit'];
    protected $casts = ['debit'=>'decimal:2','credit'=>'decimal:2'];
    public function journalEntry() { return $this->belongsTo(JournalEntry::class); }
    public function account() { return $this->belongsTo(Account::class); }
    public function costCenter() { return $this->belongsTo(CostCenter::class); }
}
