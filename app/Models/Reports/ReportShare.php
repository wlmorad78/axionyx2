<?php

namespace App\Models\Reports;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

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
