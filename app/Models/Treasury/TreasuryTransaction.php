<?php

namespace App\Models\Treasury;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Company;

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
}
