<?php

namespace App\Models\Inventory_temp;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Company;

class Unit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'code',
        'name_ar',
        'name_en',
        'symbol',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function items()
    {
        return $this->belongsToMany(Item::class, 'item_units');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
