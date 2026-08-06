<?php

namespace App\Models\Integration;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntegrationErrorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'integration_account_id',
        'entity_type',
        'entity_id',
        'error_code',
        'error_message',
    ];

    public function account()
    {
        return $this->belongsTo(IntegrationAccount::class, 'integration_account_id');
    }
}
