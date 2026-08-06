<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\HR\Employee;

class SalesTarget extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sales_targets';

    protected $fillable = [
        'sales_rep_id',
        'year',
        'month',
        'target_amount',
        'target_customers',
        'target_visits',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
    ];

    public function salesRep()
    {
        return $this->belongsTo(Employee::class, 'sales_rep_id');
    }

    public function details()
    {
        return $this->hasMany(SalesTargetDetail::class);
    }
}
