<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToCompany;

class Branch extends Model
{
    use SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'name_ar',
        'name_en',
        'country_id',
        'governorate_id',
        'city_id',
        'area_id',
        'address_line_1',
        'phone',
        'mobile',
        'email',
        'manager_employee_id',
        'is_head_office',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_head_office' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
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

    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }

    public function treasuries()
    {
        return $this->hasMany(Treasury::class);
    }
}
