<?php

namespace App\Models\Merchandising;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\CRM\Customer;
use App\Models\HR\Employee;

class MarketIssue extends Model
{
    use SoftDeletes;

    protected $table = 'market_issues';

    protected $fillable = [
        'customer_id',
        'sales_rep_id',
        'issue_date',
        'issue_type',
        'description',
        'priority',
        'status',
    ];

    protected $casts = [
        'issue_date' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'sales_rep_id');
    }
}
