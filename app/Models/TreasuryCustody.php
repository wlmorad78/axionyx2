<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TreasuryCustody extends Model
{
    use SoftDeletes;

    protected $fillable = ['employee_id', 'treasury_id', 'custody_no', 'issue_date', 'amount', 'status', 'notes'];
    protected $casts = ['issue_date' => 'date', 'amount' => 'decimal:4'];

    public function employee() { return $this->belongsTo(Employee::class); }
    public function treasury() { return $this->belongsTo(Treasury::class); }
    public function transactions() { return $this->hasMany(TreasuryCustodyTransaction::class); }
}
