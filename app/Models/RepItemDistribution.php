<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepItemDistribution extends Model
{

    protected $fillable = [
        'company_id',
        'employee_id',
        'item_id',
        'issue_order_id',
        'return_order_id',
        'loaded_qty',
        'sold_qty',
        'returned_qty',
        'remaining_qty',
        'unit_price',
        'status',
        'closed_at',
    ];

    protected $casts = [
        'loaded_qty' => 'decimal:2',
        'sold_qty' => 'decimal:2',
        'returned_qty' => 'decimal:2',
        'remaining_qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'closed_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function issueOrder(): BelongsTo
    {
        return $this->belongsTo(IssueOrder::class);
    }

    public function returnOrder(): BelongsTo
    {
        return $this->belongsTo(ReturnOrder::class);
    }
}
