<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class DriverLicense extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'driver_licenses';

    protected $fillable = [
        'company_id',
        'driver_id',
        'license_type',
        'license_number',
        'issue_date',
        'expiry_date',
        'issuing_authority',
        'file_path',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
