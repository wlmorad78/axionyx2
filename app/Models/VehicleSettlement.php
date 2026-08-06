<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleSettlement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'vehicle_id', 'sales_rep_id', 'settlement_no', 'settlement_date',
        'opening_stock_value', 'loaded_value', 'sales_value', 'collection_value',
        'return_value', 'expense_value', 'closing_stock_value',
        'cash_difference', 'stock_difference', 'status'
    ];
    protected $casts = [
        'settlement_date' => 'date',
        'opening_stock_value' => 'decimal:4', 'loaded_value' => 'decimal:4',
        'sales_value' => 'decimal:4', 'collection_value' => 'decimal:4',
        'return_value' => 'decimal:4', 'expense_value' => 'decimal:4',
        'closing_stock_value' => 'decimal:4', 'cash_difference' => 'decimal:4',
        'stock_difference' => 'decimal:4',
    ];

    public function vehicle() { return $this->belongsTo(Vehicle::class); }
    public function salesRep() { return $this->belongsTo(Employee::class, 'sales_rep_id'); }
    public function items() { return $this->hasMany(VehicleSettlementItem::class); }
}
