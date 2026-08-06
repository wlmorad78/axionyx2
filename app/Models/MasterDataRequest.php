<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class MasterDataRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'request_type_id',
        'request_no',
        'entity_type',
        'entity_id',
        'request_action',
        'request_date',
        'requested_by',
        'current_status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'request_date' => 'date',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function requestType()
    {
        return $this->belongsTo(MasterDataType::class, 'request_type_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function steps()
    {
        return $this->hasMany(MasterDataRequestStep::class);
    }

    public function history()
    {
        return $this->hasMany(MasterDataRequestHistory::class);
    }
}
