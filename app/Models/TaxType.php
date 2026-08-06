<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxType extends Model {
    use SoftDeletes;
    protected $table = 'tax_types';
    protected $fillable = ['company_id','tax_code','tax_name','tax_category','description','is_active'];
    protected $casts = ['is_active' => 'boolean'];
    public function company() { return $this->belongsTo(Company::class); }
    public function rates() { return $this->hasMany(TaxRate::class); }
    public function groupDetails() { return $this->hasMany(TaxGroupDetail::class); }
}
