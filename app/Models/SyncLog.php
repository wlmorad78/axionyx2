<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SyncLog extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = false;

    protected $table = 'sync_logs';

    protected $fillable = [
        'sync_batch_id',
        'table_name',
        'record_id',
        'operation',
        'status',
    ];

    protected $casts = [];

    public function syncBatch()
    {
        return $this->belongsTo(SyncBatch::class);
    }
}
