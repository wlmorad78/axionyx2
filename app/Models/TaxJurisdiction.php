<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxJurisdiction extends Model {
    protected $table = 'tax_jurisdictions';
    protected $fillable = ['country_id','jurisdiction_code','jurisdiction_name'];
    public function country() { return $this->belongsTo(Country::class); }
}
