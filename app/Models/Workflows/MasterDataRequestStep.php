<?php

namespace App\Models\Workflows;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\Role;
use App\Models\User;

class MasterDataRequestStep extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'master_data_request_id',
        'step_no',
        'role_id',
        'user_id',
        'status',
        'action_date',
        'notes',
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

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
