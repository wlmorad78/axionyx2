<?php
namespace App\Models\Accounting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Branch;
use App\Models\Company\Company;
use App\Models\HR\Employee;
use App\Traits\BelongsToCompany;

class ManualJournalEntry extends Model {
    use SoftDeletes, BelongsToCompany, \App\Traits\BranchScoped;
    protected $fillable = ['company_id','branch_id','entry_no','entry_date','description','status','created_by','approved_by'];
    protected $casts = ['entry_date'=>'date'];
    public function company() { return $this->belongsTo(Company::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function createdBy() { return $this->belongsTo(Employee::class, 'created_by'); }
    public function approvedBy() { return $this->belongsTo(Employee::class, 'approved_by'); }
    public function lines() { return $this->hasMany(ManualJournalEntryLine::class); }
    protected static function booted(): void {
        static::creating(function (ManualJournalEntry $model) {
            if (!$model->entry_no) {
                $last = static::withTrashed()->orderByRaw("CAST(SUBSTR(entry_no, 5) AS INTEGER) DESC")->first();
                $next = 1;
                if ($last && preg_match('/^MJE-(\d+)$/', $last->entry_no, $m)) $next = intval($m[1]) + 1;
                $model->entry_no = 'MJE-' . str_pad($next, 5, '0', STR_PAD_LEFT);
            }
        });
    }
}
