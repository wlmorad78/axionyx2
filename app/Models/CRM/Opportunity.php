<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Opportunity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'lead_id',
        'opportunity_name',
        'expected_value',
        'expected_close_date',
        'stage',
        'status',
    ];

    protected $casts = [
        'expected_close_date' => 'date',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}
