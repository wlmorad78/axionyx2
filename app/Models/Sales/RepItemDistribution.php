<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Company\Company;
use App\Models\User;
use App\Models\Employee;
use App\Models\Inventory\IssueOrder;
use App\Models\Inventory\Item;

class RepItemDistribution extends Model
{

    protected $fillable = [
        'company_id',
        'employee_id',
        'user_id',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    protected static function booted(): void
    {
        static::creating(function (RepItemDistribution $model) {
            if ($model->user_id && !$model->employee_id) {
                $employee = Employee::where('user_id', $model->user_id)->first();
                if ($employee) {
                    $model->employee_id = $employee->id;
                }
            }
        });
    }
}
