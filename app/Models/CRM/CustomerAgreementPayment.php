<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerAgreementPayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_agreement_id',
        'payment_date',
        'payment_type',
        'amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function customerAgreement()
    {
        return $this->belongsTo(CustomerAgreement::class);
    }
}
