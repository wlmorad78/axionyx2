<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToCompany;

class WorkDay extends Model
{
    use BelongsToCompany;

    protected $table = 'work_days';

    protected $fillable = [
        'company_id',
        'branch_id',
        'year',
        'month',
        'work_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'work_days' => 'array',
            'is_active' => 'boolean',
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
}
