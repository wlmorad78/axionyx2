<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterDataRequestHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'master_data_request_id',
        'action_by',
        'action_type',
        'old_status',
        'new_status',
        'notes',
        'action_date',
    ];

    protected function casts(): array
    {
        return [
            'action_date' => 'datetime',
        ];
    }

    public function masterDataRequest()
    {
        return $this->belongsTo(MasterDataRequest::class);
    }

    public function actionBy()
    {
        return $this->belongsTo(User::class, 'action_by');
    }
}
