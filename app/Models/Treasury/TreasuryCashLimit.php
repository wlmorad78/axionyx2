<?php
namespace App\Models\Treasury;

use Illuminate\Database\Eloquent\Model;

class TreasuryCashLimit extends Model
{
    protected $fillable = ['treasury_id', 'minimum_limit', 'maximum_limit', 'alert_limit'];
    protected $casts = ['minimum_limit' => 'decimal:4', 'maximum_limit' => 'decimal:4', 'alert_limit' => 'decimal:4'];

    public function treasury() { return $this->belongsTo(Treasury::class); }
}
