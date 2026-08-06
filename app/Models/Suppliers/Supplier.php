<?php
namespace App\Models\Suppliers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToCompany;
use App\Traits\BranchScoped;
use App\Models\Settings\City;
use App\Models\Company\Company;
use App\Models\Settings\Country;
use App\Models\Settings\District;
use App\Models\Settings\Governorate;

class Supplier extends Model {
    use SoftDeletes, BelongsToCompany, BranchScoped;
    protected $fillable = ['company_id','branch_id','supplier_group_id','supplier_code','supplier_name','tax_number','commercial_register','country_id','governorate_id','city_id','district_id','address','phone','mobile','email','credit_limit','payment_term_days','opening_balance','is_active'];
    protected $casts = ['credit_limit'=>'decimal:2','opening_balance'=>'decimal:2','is_active'=>'boolean','payment_term_days'=>'integer'];
    public function company() { return $this->belongsTo(Company::class); }
    public function supplierGroup() { return $this->belongsTo(SupplierGroup::class); }
    public function country() { return $this->belongsTo(Country::class); }
    public function governorate() { return $this->belongsTo(Governorate::class); }
    public function city() { return $this->belongsTo(City::class); }
    public function district() { return $this->belongsTo(District::class); }
    public function contacts() { return $this->hasMany(SupplierContact::class); }
    protected static function booted(): void {
        static::creating(function (Supplier $model) {
            if (!$model->supplier_code) {
                $last = static::orderByRaw("CAST(SUBSTR(supplier_code, 5) AS INTEGER) DESC")->first();
                $next = 1;
                if ($last && preg_match('/^SUP-(\d+)$/', $last->supplier_code, $m)) $next = intval($m[1]) + 1;
                $model->supplier_code = 'SUP-' . str_pad($next, 5, '0', STR_PAD_LEFT);
            }
        });
    }
}
