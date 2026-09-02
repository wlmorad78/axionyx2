<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TreasuryTransaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'treasury_id',
        'type',
        'amount',
        'reference_type',
        'reference_id',
        'description',
        'transaction_date',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    public function treasury()
    {
        return $this->belongsTo(Treasury::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }
}
