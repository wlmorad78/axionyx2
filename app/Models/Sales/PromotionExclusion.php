<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromotionExclusion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'promotion_exclusions';

    protected $fillable = [
        'sales_incentive_id',
        'excluded_incentive_id',
    ];

    protected $casts = [];

    public function salesIncentive()
    {
        return $this->belongsTo(SalesIncentive::class);
    }

    public function excludedIncentive()
    {
        return $this->belongsTo(SalesIncentive::class, 'excluded_incentive_id');
    }
}
