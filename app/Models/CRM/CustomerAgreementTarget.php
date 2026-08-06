<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerAgreementTarget extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_agreement_id',
        'period_from',
        'period_to',
        'target_amount',
        'achieved_amount',
        'achievement_percent',
    ];

    protected function casts(): array
    {
        return [
            'period_from' => 'date',
            'period_to' => 'date',
            'target_amount' => 'decimal:2',
            'achieved_amount' => 'decimal:2',
            'achievement_percent' => 'decimal:2',
        ];
    }

    public function customerAgreement()
    {
        return $this->belongsTo(CustomerAgreement::class);
    }
}
