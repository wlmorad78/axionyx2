<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IssueOrderItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'issue_order_id',
        'item_id',
        'item_unit_id',
        'unit_id',
        'conversion_factor',
        'base_quantity',
        'batch_id',
        'requested_quantity',
        'issued_quantity',
        'purchase_price',
        'sales_price',
        'total_amount',
        'notes',
    ];

    public function issueOrder()
    {
        return $this->belongsTo(IssueOrder::class);
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