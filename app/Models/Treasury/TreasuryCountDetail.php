<?php
namespace App\Models\Treasury;

use Illuminate\Database\Eloquent\Model;

class TreasuryCountDetail extends Model
{
    protected $fillable = ['treasury_count_id', 'denomination', 'qty', 'total_amount'];
    protected $casts = ['qty' => 'integer', 'total_amount' => 'decimal:4'];

    public function count() { return $this->belongsTo(TreasuryCount::class); }
}
