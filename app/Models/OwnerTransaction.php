<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Treasury;
use App\Models\Warehouse;
use App\Models\Item;

class OwnerTransaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'transaction_type',
        'amount',
        'item_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'treasury_id',
        'warehouse_id',
        'reference_type',
        'reference_id',
        'description',
        'transaction_date',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function treasury()
    {
        return $this->belongsTo(Treasury::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\Employee::class, 'created_by');
    }

    public function reference()
    {
        return $this->morphTo();
    }

    // ثوابت أنواع المعاملات
    const TYPE_CASH_DEPOSIT = 'cash_deposit';
    const TYPE_CASH_WITHDRAWAL = 'cash_withdrawal';
    const TYPE_GOODS_DISPATCH = 'goods_dispatch';
    const TYPE_GOODS_RECEIVE = 'goods_receive';

    public static function types()
    {
        return [
            self::TYPE_CASH_DEPOSIT => 'إيداع نقدي',
            self::TYPE_CASH_WITHDRAWAL => 'سحب نقدي',
            self::TYPE_GOODS_DISPATCH => 'إرسال بضاعة',
            self::TYPE_GOODS_RECEIVE => 'سحب بضاعة',
        ];
    }

    public function getTypeLabelAttribute()
    {
        return self::types()[$this->transaction_type] ?? $this->transaction_type;
    }
}
