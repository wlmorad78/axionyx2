<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerMarketingSupport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_agreement_id',
        'marketing_support_type_id',
        'support_value',
        'issue_date',
        'expiry_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'support_value' => 'decimal:2',
            'issue_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function customerAgreement()
    {
        return $this->belongsTo(CustomerAgreement::class);
    }

    public function marketingSupportType()
    {
        return $this->belongsTo(MarketingSupportType::class);
    }
}
