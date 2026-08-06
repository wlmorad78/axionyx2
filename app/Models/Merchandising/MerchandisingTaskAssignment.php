<?php
namespace App\Models\Merchandising;

use Illuminate\Database\Eloquent\Model;
use App\Models\CRM\Customer;
use App\Models\HR\Employee;

class MerchandisingTaskAssignment extends Model
{
    protected $fillable = ['merchandising_task_id', 'customer_id', 'sales_rep_id', 'assigned_date', 'due_date', 'status'];
    protected $casts = ['assigned_date' => 'date', 'due_date' => 'date'];

    public function task() { return $this->belongsTo(MerchandisingTask::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function salesRep() { return $this->belongsTo(Employee::class, 'sales_rep_id'); }
}
