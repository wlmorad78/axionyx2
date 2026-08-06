<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreasuryOpeningBalance extends Model
{
    protected $fillable = ['company_id', 'treasury_id', 'fiscal_year_id', 'opening_balance'];
    protected $casts = ['opening_balance' => 'decimal:4'];

    public function company() { return $this->belongsTo(Company::class); }
    public function treasury() { return $this->belongsTo(Treasury::class); }
    public function fiscalYear() { return $this->belongsTo(FiscalYear::class); }
}
