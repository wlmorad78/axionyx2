<?php

namespace App\Models\Surveys;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\CRM\Customer;
use App\Models\HR\Employee;

class CompetitorPhoto extends Model
{
    use SoftDeletes;

    protected $table = 'competitor_photos';

    protected $fillable = [
        'customer_id',
        'sales_rep_id',
        'competitor_id',
        'photo_type',
        'file_path',
        'taken_at',
    ];

    protected $casts = [
        'taken_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'sales_rep_id');
    }

    public function competitor(): BelongsTo
    {
        return $this->belongsTo(Competitor::class);
    }
}
