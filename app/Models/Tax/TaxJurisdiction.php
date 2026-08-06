<?php
namespace App\Models\Tax;

use Illuminate\Database\Eloquent\Model;
use App\Models\Settings\Country;

class TaxJurisdiction extends Model {
    protected $table = 'tax_jurisdictions';
    protected $fillable = ['country_id','jurisdiction_code','jurisdiction_name'];
    public function country() { return $this->belongsTo(Country::class); }
}
