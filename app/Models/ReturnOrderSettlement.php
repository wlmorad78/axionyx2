<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReturnOrderSettlement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'settlement_no',
        'return_order_id',
        'employee_id',
        'warehouse_id',
        'load_request_no',
        'status',
        'total_loaded',
        'total_sold',
        'total_returned',
        'total_received',
        'total_difference',
        'total_financial_difference',
        'total_debt',
        'total_credit',
        'notes',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'total_loaded' => 'decimal:2',
        'total_sold' => 'decimal:2',
        'total_returned' => 'decimal:2',
        'total_received' => 'decimal:2',
        'total_difference' => 'decimal:2',
        'total_financial_difference' => 'decimal:2',
        'total_debt' => 'decimal:2',
        'total_credit' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ReturnOrderSettlement $model) {
            if (empty($model->settlement_no)) {
                $model->settlement_no = self::generateSettlementNo();
            }
        });
    }

    public static function generateSettlementNo(): string
    {
        $last = self::withTrashed()->orderByDesc('id')->value('settlement_no');
        $nextNumber = 1;
        if ($last && preg_match('/(\d+)$/', $last, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        }
        return 'ROS-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    public function items()
    {
        return $this->hasMany(ReturnOrderSettlementItem::class, 'settlement_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeByEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }
}
