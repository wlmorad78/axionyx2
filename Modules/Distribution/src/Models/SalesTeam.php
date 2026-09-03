<?php

namespace App\Modules\Distribution\src\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesTeam extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'name_en',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function salesmen()
    {
        return $this->hasMany(Salesman::class, 'sales_team_id');
    }
}
