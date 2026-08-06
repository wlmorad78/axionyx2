<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Branch;
use App\Models\Company\Company;
use App\Models\HR\Employee;

class DailyDistributionDashboard extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'dashboard_date',
        'sales_rep_id',
        'route_id',
        'planned_customers',
        'visited_customers',
        'invoices_count',
        'sales_amount',
        'returns_amount',
        'collections_amount',
        'loaded_amount',
        'settled_amount',
        'cash_difference',
    ];

    protected $casts = [
        'dashboard_date' => 'date',
        'planned_customers' => 'integer',
        'visited_customers' => 'integer',
        'invoices_count' => 'integer',
        'sales_amount' => 'decimal:2',
        'returns_amount' => 'decimal:2',
        'collections_amount' => 'decimal:2',
        'loaded_amount' => 'decimal:2',
        'settled_amount' => 'decimal:2',
        'cash_difference' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function salesRep()
    {
        return $this->belongsTo(Employee::class, 'sales_rep_id');
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }
}
