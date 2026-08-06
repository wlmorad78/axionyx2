<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToCompany;
use App\Models\Settings\City;
use App\Models\Company\Company;
use App\Models\Settings\Country;
use App\Models\Settings\District;
use App\Models\Settings\Governorate;
use App\Models\User;

class Employee extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $appends = ['full_name_ar', 'full_name_en'];

    protected $fillable = [
        'company_id', 'user_id', 'employee_code',
        'first_name_ar', 'second_name_ar', 'third_name_ar', 'last_name_ar',
        'first_name_en', 'second_name_en', 'third_name_en', 'last_name_en',
        'national_id', 'passport_number', 'birth_date', 'gender', 'marital_status',
        'mobile', 'phone', 'email',
        'country_id', 'governorate_id', 'city_id', 'area_id', 'address_line_1',
        'employee_status_id', 'department_id', 'job_position_id', 'hire_date', 'termination_date',
        'photo_attachment_id', 'notes', 'is_active',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'birth_date' => 'date',
            'hire_date' => 'date',
            'termination_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function company() { return $this->belongsTo(Company::class); }
    public function user() { return $this->belongsTo(User::class, 'user_id', 'id'); }
    public function country() { return $this->belongsTo(Country::class); }
    public function governorate() { return $this->belongsTo(Governorate::class); }
    public function city() { return $this->belongsTo(City::class); }
    public function area() { return $this->belongsTo(District::class, 'area_id'); }
    public function status() { return $this->belongsTo(EmployeeStatus::class, 'employee_status_id'); }
    public function department() { return $this->belongsTo(Department::class); }
    public function jobPosition() { return $this->belongsTo(JobPosition::class); }

    public function getFullNameArAttribute(): string
    {
        $parts = array_filter([
            $this->first_name_ar,
            $this->second_name_ar,
            $this->third_name_ar,
            $this->last_name_ar,
        ], fn($p) => $p !== null && trim($p) !== '');

        if (empty($parts)) return '';

        $fullName = trim(implode(' ', $parts));

        // If first_name_ar already contains the full name, use it directly
        if (!empty($this->first_name_ar) && str_starts_with(trim($fullName), trim($this->first_name_ar))) {
            return trim($this->first_name_ar);
        }

        return $fullName;
    }

    public function getFullNameEnAttribute(): string
    {
        return trim(implode(' ', [
            $this->first_name_en,
            $this->second_name_en,
            $this->third_name_en,
            $this->last_name_en,
        ]));
    }
}
