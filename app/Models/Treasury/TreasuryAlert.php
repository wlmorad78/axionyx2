<?php
namespace App\Models\Treasury;

use Illuminate\Database\Eloquent\Model;

class TreasuryAlert extends Model
{
    protected $fillable = ['treasury_id', 'alert_type', 'alert_date', 'message', 'status'];
    protected $casts = ['alert_date' => 'date'];

    public function treasury() { return $this->belongsTo(Treasury::class); }
}
