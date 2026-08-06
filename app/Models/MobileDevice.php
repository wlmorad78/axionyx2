<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MobileDevice extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mobile_devices';

    protected $fillable = [
        'device_uuid',
        'device_name',
        'sales_rep_id',
        'last_sync_at',
        'status',
    ];

    protected $casts = [
        'last_sync_at' => 'datetime',
    ];

    public function salesRep()
    {
        return $this->belongsTo(Employee::class, 'sales_rep_id');
    }
}
