<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromotionPriority extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'promotion_priorities';

    protected $fillable = [
        'sales_incentive_id',
        'priority',
        'allow_combination',
    ];

    protected $casts = [];

    public function salesIncentive()
    {
        return $this->belongsTo(SalesIncentive::class);
    }
}
