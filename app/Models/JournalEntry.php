<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToCompany;

class JournalEntry extends Model {
    use SoftDeletes, BelongsToCompany, \App\Traits\BranchScoped;
    protected $fillable = ['company_id','branch_id','journal_entry_type_id','entry_no','entry_date','reference_type','reference_id','description','total_debit','total_credit','status','created_by','approved_by'];
    protected $casts = ['entry_date'=>'date','total_debit'=>'decimal:2','total_credit'=>'decimal:2'];
    public function company() { return $this->belongsTo(Company::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function journalEntryType() { return $this->belongsTo(JournalEntryType::class); }
    public function createdBy() { return $this->belongsTo(Employee::class, 'created_by'); }
    public function approvedBy() { return $this->belongsTo(Employee::class, 'approved_by'); }
    public function lines() { return $this->hasMany(JournalEntryLine::class); }
    protected static function booted(): void {
        static::creating(function (JournalEntry $model) {
            if (!$model->entry_no) {
                $query = static::withTrashed();
                if ($model->company_id) $query->where('company_id', $model->company_id);
                $last = $query->orderByRaw("CAST(SUBSTR(entry_no, 4) AS INTEGER) DESC")->first();
                $next = 1;
                if ($last && preg_match('/^JE-(\d+)$/', $last->entry_no, $m)) $next = intval($m[1]) + 1;
                $model->entry_no = 'JE-' . str_pad($next, 5, '0', STR_PAD_LEFT);
            }
        });
    }
}
