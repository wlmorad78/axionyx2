<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class AssetAssignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'asset_assignments';

    protected $fillable = [
        'asset_id',
        'user_id',
        'assigned_date',
        'returned_date',
        'status',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'returned_date' => 'date',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
