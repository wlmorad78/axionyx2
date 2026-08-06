<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToCompany;

class SupplierGroup extends Model {
    use SoftDeletes, BelongsToCompany;
    protected $fillable = ['company_id','code','name_ar','name_en','notes','is_active'];
    protected $casts = ['is_active'=>'boolean'];
    public function company() { return $this->belongsTo(Company::class); }
    public function suppliers() { return $this->hasMany(Supplier::class); }
}
