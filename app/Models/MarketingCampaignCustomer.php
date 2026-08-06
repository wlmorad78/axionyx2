<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketingCampaignCustomer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'marketing_campaign_customers';

    protected $fillable = [
        'marketing_campaign_id',
        'customer_id',
        'target_amount',
        'actual_amount',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
    ];

    public function campaign()
    {
        return $this->belongsTo(MarketingCampaign::class, 'marketing_campaign_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
