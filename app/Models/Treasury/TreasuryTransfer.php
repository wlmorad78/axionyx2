<?php
namespace App\Models\Treasury;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Company;

class TreasuryTransfer extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'from_treasury_id', 'to_treasury_id', 'transfer_no', 'transfer_date', 'amount', 'notes', 'status'];
    protected $casts = ['transfer_date' => 'date', 'amount' => 'decimal:4'];

    public function company() { return $this->belongsTo(Company::class); }
    public function fromTreasury() { return $this->belongsTo(Treasury::class, 'from_treasury_id'); }
    public function toTreasury() { return $this->belongsTo(Treasury::class, 'to_treasury_id'); }
}
