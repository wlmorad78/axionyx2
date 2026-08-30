<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoadRequestItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'load_request_id',
        'item_id',
        'unit_id',
        'quantity',
        'unit_price',
        'total_price',
        'conversion_factor',
        'base_quantity',
        'notes',
    ];

    public function loadRequest()
    {
        return $this->belongsTo(LoadRequest::class);
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
