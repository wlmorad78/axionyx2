<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerAgreement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'agreement_no',
        'agreement_type_id',
        'customer_id',
        'start_date',
        'end_date',
        'agreement_value',
        'notes',
        'status',
        'created_by',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'agreement_value' => 'decimal:2',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function agreementType()
    {
        return $this->belongsTo(CustomerAgreementType::class, 'agreement_type_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(CustomerAgreementItem::class);
    }

    public function marketingSupports()
    {
        return $this->hasMany(CustomerMarketingSupport::class);
    }

    public function rebateRules()
    {
        return $this->hasMany(CustomerRebateRule::class);
    }

    public function targets()
    {
        return $this->hasMany(CustomerAgreementTarget::class);
    }

    public function payments()
    {
        return $this->hasMany(CustomerAgreementPayment::class);
    }

    public function history()
    {
        return $this->hasMany(CustomerAgreementHistory::class);
    }
}
