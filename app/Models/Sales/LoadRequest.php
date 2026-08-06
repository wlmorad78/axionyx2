<?php
namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Branch;
use App\Models\Company\Company;
use App\Models\HR\Employee;
use App\Models\Inventory\IssueOrder;
use App\Models\Inventory\Warehouse;

class LoadRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'warehouse_id', 'request_no',
        'employee_id', 'supervisor_employee_id', 'sales_territory_id',
        'trip_date', 'load_type', 'priority', 'request_date',
        'status', 'total_items_count', 'total_quantity', 'total_amount',
        'requested_by', 'create_by', 'create_at', 'create_notes', 'notes',
    ];

    protected $casts = [
        'trip_date' => 'date',
        'request_date' => 'date',
        'create_at' => 'datetime',
        'total_items_count' => 'integer',
        'total_quantity' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function employee() { return $this->belongsTo(Employee::class); }
    public function supervisorEmployee() { return $this->belongsTo(Employee::class, 'supervisor_employee_id'); }
    public function salesTerritory() { return $this->belongsTo(SalesTerritory::class); }
    public function requestedByEmployee() { return $this->belongsTo(Employee::class, 'requested_by'); }
    public function createByEmployee() { return $this->belongsTo(Employee::class, 'create_by'); }
    public function items() { return $this->hasMany(LoadRequestItem::class); }
    public function issueOrder() { return $this->hasOne(IssueOrder::class); }
    public function returnOrder() { return $this->hasOne(\App\Models\ReturnOrder::class); }

    protected static function booted(): void
    {
        static::creating(function (LoadRequest $model) {
            if (!$model->request_no) {
                $maxNo = static::withoutGlobalScopes()
                    ->where('company_id', $model->company_id)
                    ->max(\DB::raw("CAST(SUBSTR(request_no, 6) AS INTEGER)"));
                $next = ($maxNo && is_numeric($maxNo)) ? intval($maxNo) + 1 : 1;
                $model->request_no = 'LREQ-' . str_pad($next, 5, '0', STR_PAD_LEFT);

                $attempts = 0;
                while ($attempts < 10) {
                    $exists = static::withoutGlobalScopes()
                        ->where('company_id', $model->company_id)
                        ->where('request_no', $model->request_no)
                        ->exists();
                    if (!$exists) break;
                    $attempts++;
                    $next++;
                    $model->request_no = 'LREQ-' . str_pad($next, 5, '0', STR_PAD_LEFT);
                }
            }
        });
    }
}
