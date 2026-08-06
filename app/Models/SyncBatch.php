<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SyncBatch extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = false;

    protected $table = 'sync_batches';

    protected $fillable = [
        'device_id',
        'sales_rep_id',
        'sync_start',
        'sync_end',
        'status',
    ];

    protected $casts = [
        'sync_start' => 'datetime',
        'sync_end' => 'datetime',
    ];

    public function salesRep()
    {
        return $this->belongsTo(Employee::class, 'sales_rep_id');
    }

    public function logs()
    {
        return $this->hasMany(SyncLog::class);
    }
}
