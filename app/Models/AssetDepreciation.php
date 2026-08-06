<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class AssetDepreciation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'asset_depreciations';

    protected $fillable = [
        'asset_id',
        'depreciation_date',
        'amount',
    ];

    protected $casts = [
        'depreciation_date' => 'date',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
