<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Company\Company;
use App\Models\HR\Employee;

class DailyClosing extends Model
{
    protected $table = 'daily_closings';

    protected $fillable = ['company_id', 'sector', 'closing_date', 'status', 'closed_by', 'notes'];

    protected $casts = ['closing_date' => 'date'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function closedBy()
    {
        return $this->belongsTo(Employee::class, 'closed_by');
    }
}
