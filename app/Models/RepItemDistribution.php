<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepItemDistribution extends Model
{
    protected $fillable = [
        'company_id',
        'employee_id',
        'user_id',
        'item_id',
        'issue_order_id',
        'return_order_id',
        'loaded_qty',
        'sold_qty',
        'returned_qty',
        'remaining_qty',
        'unit_price',
        'status',
        'closed_at',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function issueOrder()
    {
        return $this->belongsTo(IssueOrder::class);
    }

    public function returnOrder()
    {
        return $this->belongsTo(ReturnOrder::class);
    }
}
