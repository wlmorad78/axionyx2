<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketingCampaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'marketing_campaigns';

    protected $fillable = [
        'company_id',
        'campaign_code',
        'campaign_name',
        'start_date',
        'end_date',
        'budget',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function campaignCustomers()
    {
        return $this->hasMany(MarketingCampaignCustomer::class, 'marketing_campaign_id');
    }
}
