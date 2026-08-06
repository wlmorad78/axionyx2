<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class DriverTraining extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'driver_training';

    protected $fillable = [
        'driver_id',
        'training_name',
        'training_type',
        'training_date',
        'expiry_date',
        'provider',
        'certificate_no',
        'file_path',
        'notes',
    ];

    protected $casts = [
        'training_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
