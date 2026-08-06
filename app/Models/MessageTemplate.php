<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MessageTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'message_templates';

    protected $fillable = [
        'template_code',
        'template_name',
        'message_body',
        'channel',
    ];

    protected $casts = [];

    public function logs()
    {
        return $this->hasMany(MessageLog::class, 'template_id');
    }
}
