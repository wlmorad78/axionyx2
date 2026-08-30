<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToCompany;
use App\Traits\BranchScoped;

class Customer extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany, BranchScoped;

    protected $fillable = [
        'company_id',
        'branch_id',
        'customer_group_id',
        'customer_class_id',
        'customer_type_id',
        'customer_account_type_id',
        'trade_program_type_id',
        'cus_sings',
        'governorate_id',
        'city_id',
        'area_id',
        'code',
        'pos_code',
        'name_ar',
        'name_en',
        'national_id',
        'responsible_person',
        'location_mark',
        'tax_number',
        'commercial_register',
        'sales_territory_id',
        'route_line_id',
        'phone',
        'mobile',
        'has_whatsapp',
        'whatsapp_number',
        'email',
        'credit_limit',
        'payment_term_days',
        'account_type',
        'trade_program_type',
        'pos_material',
        'is_active',
        'notes',
        'address_line',
        'latitude',
        'longitude',
        'average_withdrawals',
        'opening_balance',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'payment_term_days' => 'integer',
            'is_active' => 'boolean',
            'has_whatsapp' => 'boolean',
            'cus_sings' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'average_withdrawals' => 'decimal:2',
            'opening_balance' => 'decimal:2',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function governorate()
    {
        return $this->belongsTo(Governorate::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function area()
    {
        return $this->belongsTo(District::class, 'area_id');
    }

    public function customerGroup()
    {
        return $this->belongsTo(CustomerGroup::class);
    }

    public function customerClass()
    {
        return $this->belongsTo(CustomerClass::class);
    }

    public function customerType()
    {
        return $this->belongsTo(CustomerType::class);
    }

    public function customerAccountType()
    {
        return $this->belongsTo(CustomerAccountType::class);
    }

    public function tradeProgramType()
    {
        return $this->belongsTo(TradeProgramType::class);
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function contacts()
    {
        return $this->hasMany(CustomerContact::class);
    }

    public function creditLimits()
    {
        return $this->hasMany(CustomerCreditLimit::class);
    }
}
