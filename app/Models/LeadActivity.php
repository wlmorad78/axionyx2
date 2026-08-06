<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class LeadActivity extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'lead_activities';

    protected $fillable = [
        'lead_id',
        'activity_date',
        'activity_type',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'activity_date' => 'date',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
