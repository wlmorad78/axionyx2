<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToCompany;
use App\Scopes\CompanyIsolationScope;
use App\Models\Company;

class UserType extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    /**
     * عزل تلقائي على مستوى الشركة فقط (بدون فرع).
     */
    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope(new CompanyIsolationScope);
    }

    protected $fillable = [
        'company_id',
        'code',
        'name_ar',
        'name_en',
        'description',
        'is_active',
        'is_protected',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_protected' => 'boolean',
        ];
    }

    /**
     * اسم نوع المستخدم المعروض (مطابق لتصميم userType->name).
     */
    public function getNameAttribute(): string
    {
        return $this->name_ar;
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
