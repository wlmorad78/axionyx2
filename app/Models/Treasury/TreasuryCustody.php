<?php
namespace App\Models\Treasury;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class TreasuryCustody extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'treasury_id', 'custody_no', 'issue_date', 'amount', 'status', 'notes'];
    protected $casts = ['issue_date' => 'date', 'amount' => 'decimal:4'];

    public function user() { return $this->belongsTo(User::class); }
    public function treasury() { return $this->belongsTo(Treasury::class); }
    public function transactions() { return $this->hasMany(TreasuryCustodyTransaction::class); }
}
