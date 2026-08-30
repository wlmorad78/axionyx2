<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToCompany;
use App\Models\Company;
use App\Models\Item;
use App\Models\Unit;

class RepDailySettlementItem extends Model
{
    use SoftDeletes, BelongsToCompany;

    protected $table = 'rep_daily_settlement_items';

    protected $fillable = [
        'company_id',
        'settlement_id',
        'item_id',
        'unit_id',
        'item_code',
        'item_name',
        'loaded_qty',
        'sold_qty',
        'returned_qty',
        'remaining_qty',
        'unit_price',
        'line_total',
        'transfer_in_qty',
        'transfer_out_qty',
        'notes',
    ];

    protected $casts = [
        'loaded_qty' => 'decimal:2',
        'sold_qty' => 'decimal:2',
        'returned_qty' => 'decimal:2',
        'remaining_qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'transfer_in_qty' => 'decimal:2',
        'transfer_out_qty' => 'decimal:2',
    ];

    // ─── Relationships ──────────────────────────────────────

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function settlement()
    {
        return $this->belongsTo(RepDailySettlement::class, 'settlement_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
