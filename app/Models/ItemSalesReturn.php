<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToCompany;
use App\Traits\BranchScoped;
use App\Models\User;

class ItemSalesReturn extends Model
{
    use BelongsToCompany, BranchScoped;

    protected $fillable = [
        'company_id',
        'branch_id',
        'warehouse_id',
        'user_id',
        'item_id',
        'date',
        'item_code',
        'loaded_qty',
        'sold_qty',
        'returned_qty',
    ];

    protected $casts = [
        'date' => 'date',
        'loaded_qty' => 'decimal:2',
        'sold_qty' => 'decimal:2',
        'returned_qty' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
