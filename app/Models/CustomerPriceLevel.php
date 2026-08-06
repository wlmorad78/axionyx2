<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerPriceLevel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'customer_price_levels';

    protected $fillable = [
        'customer_id',
        'price_level_id',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function priceLevel()
    {
        return $this->belongsTo(PriceLevel::class);
    }
}
