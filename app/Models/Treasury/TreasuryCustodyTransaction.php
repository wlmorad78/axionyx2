<?php
namespace App\Models\Treasury;

use Illuminate\Database\Eloquent\Model;

class TreasuryCustodyTransaction extends Model
{
    protected $fillable = ['treasury_custody_id', 'transaction_date', 'transaction_type', 'amount', 'notes'];
    protected $casts = ['transaction_date' => 'date', 'amount' => 'decimal:4'];

    public function custody() { return $this->belongsTo(TreasuryCustody::class); }
}
