<?php

namespace App\Models\Merchandising;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Company\Company;
use App\Models\CRM\Customer;
use App\Models\HR\Employee;

class ShelfShareSurvey extends Model
{
    use SoftDeletes;

    protected $table = 'shelf_share_surveys';

    protected $fillable = [
        'company_id',
        'customer_id',
        'sales_rep_id',
        'survey_date',
        'notes',
    ];

    protected $casts = [
        'survey_date' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'sales_rep_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShelfShareItem::class);
    }
}
