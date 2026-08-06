<?php

namespace App\Models\Company;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\HR\AttendanceRecord;
use App\Models\Settings\City;
use App\Models\Sales\Collection;
use App\Models\Settings\CompanySubscription;
use App\Models\Settings\Country;
use App\Models\Settings\Currency;
use App\Models\CRM\Customer;
use App\Models\Settings\District;
use App\Models\HR\Employee;
use App\Models\Settings\Governorate;
use App\Models\Sales\SalesInvoice;
use App\Models\Settings\Street;
use App\Models\Suppliers\Supplier;
use App\Models\Treasury\Treasury;
use App\Models\User;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'commercial_name_ar',
        'commercial_name_en',
        'tax_number',
        'commercial_register',
        'currency_id',
        'country_id',
        'governorate_id',
        'city_id',
        'area_id',
        'street_id',
        'address_line_1',
        'address_line_2',
        'postal_code',
        'phone',
        'mobile',
        'email',
        'website',
        'logo_attachment_id',
        'is_active',
        'notes',
        // Financial settings
        'tax_rate',
        'default_bank',
        'default_treasury',
        // Sales settings
        'default_price_list',
        'default_price_level',
        'max_discount',
        'max_credit',
        // Inventory settings
        'default_warehouse',
        'low_stock_alert',
        'min_stock',
        'default_vehicle',
        // HR settings
        'work_start',
        'work_end',
        'late_grace',
        'salary_currency',
        'week_start',
        // Notification settings
        'sales_notifications',
        'stock_alerts',
        'customer_notifications',
        'email_notifications',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
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

    public function street()
    {
        return $this->belongsTo(Street::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(CompanySubscription::class);
    }

    public function subscription()
    {
        return $this->hasOne(CompanySubscription::class)->latestOfMany();
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'company_user');
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function suppliers()
    {
        return $this->hasMany(Supplier::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function invoices()
    {
        return $this->hasMany(SalesInvoice::class);
    }

    public function customerPayments()
    {
        return $this->hasMany(Collection::class);
    }

    public function treasuries()
    {
        return $this->hasMany(Treasury::class);
    }

    public function attendances()
    {
        return $this->hasMany(AttendanceRecord::class);
    }
}
