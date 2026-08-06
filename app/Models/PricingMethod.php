<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PricingMethod extends Model {
    use SoftDeletes;
    protected $table = 'pricing_methods';
    protected $fillable = ['company_id','method_code','method_name','description','is_active'];
    protected $casts = ['is_active' => 'boolean'];
    public function company() { return $this->belongsTo(Company::class); }
    public function rules() { return $this->hasMany(PricingRule::class); }
}
