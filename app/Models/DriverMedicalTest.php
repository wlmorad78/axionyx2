<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class DriverMedicalTest extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'driver_medical_tests';

    protected $fillable = [
        'driver_id',
        'test_type',
        'test_date',
        'result',
        'next_test_date',
        'doctor_name',
        'file_path',
        'notes',
    ];

    protected $casts = [
        'test_date' => 'date',
        'next_test_date' => 'date',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
