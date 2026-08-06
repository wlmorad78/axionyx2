<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompetitorPriceSurvey extends Model
{
    use SoftDeletes;

    protected $table = 'competitor_price_surveys';

    protected $fillable = [
        'company_id',
        'sales_rep_id',
        'customer_id',
        'visit_id',
        'survey_date',
        'notes',
    ];

    protected $casts = [
        'survey_date' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'sales_rep_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(CustomerVisit::class, 'visit_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CompetitorPriceSurveyItem::class);
    }
}
