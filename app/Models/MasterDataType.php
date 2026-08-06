<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class MasterDataType extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'master_data_request_types';

    protected $fillable = [
        'code',
        'name',
        'entity_name',
        'is_active',
    ];

    public function requests()
    {
        return $this->hasMany(MasterDataRequest::class, 'request_type_id');
    }
}
