<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportShare extends Model
{
    protected $fillable = [
        'report_definition_id',
        'user_id',
        'permission',
    ];

    public function report()
    {
        return $this->belongsTo(ReportDefinition::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
