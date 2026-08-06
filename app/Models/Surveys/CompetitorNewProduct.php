<?php

namespace App\Models\Surveys;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\CRM\Customer;
use App\Models\HR\Employee;

class CompetitorNewProduct extends Model
{
    use SoftDeletes;

    protected $table = 'competitor_new_products';

    protected $fillable = [
        'competitor_id',
        'competitor_product_id',
        'reported_by',
        'customer_id',
        'report_date',
        'notes',
    ];

    protected $casts = [
        'report_date' => 'date',
    ];

    public function competitor(): BelongsTo
    {
        return $this->belongsTo(Competitor::class);
    }

    public function competitorProduct(): BelongsTo
    {
        return $this->belongsTo(CompetitorProduct::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reported_by');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
