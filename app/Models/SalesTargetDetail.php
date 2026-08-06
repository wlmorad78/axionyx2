<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesTargetDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sales_target_details';

    protected $fillable = [
        'sales_target_id',
        'customer_id',
        'target_amount',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
    ];

    public function salesTarget()
    {
        return $this->belongsTo(SalesTarget::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
