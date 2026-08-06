<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MerchandisingVisit extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'merchandising_visits';

    protected $fillable = [
        'company_id',
        'sales_rep_id',
        'customer_id',
        'visit_date',
        'visit_time',
        'latitude',
        'longitude',
        'overall_score',
        'notes',
    ];

    protected $casts = [
        'visit_date' => 'date',
        'overall_score' => 'decimal:2',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function salesRep()
    {
        return $this->belongsTo(Employee::class, 'sales_rep_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function details()
    {
        return $this->hasMany(MerchandisingVisitDetail::class, 'merchandising_visit_id');
    }

    public function photos()
    {
        return $this->hasMany(MerchandisingPhoto::class, 'merchandising_visit_id');
    }
}
