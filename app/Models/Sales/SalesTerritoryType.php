<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalesTerritoryType extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sales_territory_types';

    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'is_system',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
