<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MerchandisingAudit extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'customer_id', 'sales_rep_id', 'visit_id', 'audit_date', 'audit_time', 'latitude', 'longitude', 'overall_score', 'notes'];
    protected $casts = ['audit_date' => 'date', 'latitude' => 'decimal:7', 'longitude' => 'decimal:7', 'overall_score' => 'decimal:2'];

    public function company() { return $this->belongsTo(Company::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function salesRep() { return $this->belongsTo(Employee::class, 'sales_rep_id'); }
    public function visit() { return $this->belongsTo(CustomerVisit::class, 'visit_id'); }
    public function details() { return $this->hasMany(MerchandisingAuditDetail::class); }
    public function shelfAudits() { return $this->hasMany(ShelfAudit::class); }
    public function availabilityAudits() { return $this->hasMany(AvailabilityAudit::class); }
    public function refrigeratorAudits() { return $this->hasMany(RefrigeratorAudit::class); }
    public function posmAudits() { return $this->hasMany(PosmAudit::class); }
    public function photos() { return $this->hasMany(MerchandisingAuditPhoto::class); }
}
