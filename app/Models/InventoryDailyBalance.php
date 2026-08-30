<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Company;
use App\Models\Item;
use App\Models\Warehouse;

class InventoryDailyBalance extends Model
{
    protected $table = 'inventory_daily_balances';

    protected $fillable = [
        'company_id', 'warehouse_id', 'item_id', 'balance_date',
        'opening_balance', 'incoming_qty', 'outgoing_qty', 'closing_balance',
    ];

    protected $casts = [
        'balance_date'   => 'date',
        'opening_balance' => 'decimal:4',
        'incoming_qty'   => 'decimal:4',
        'outgoing_qty'   => 'decimal:4',
        'closing_balance' => 'decimal:4',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
