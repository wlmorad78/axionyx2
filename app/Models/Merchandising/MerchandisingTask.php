<?php
namespace App\Models\Merchandising;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Company;

class MerchandisingTask extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'task_name', 'description', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function company() { return $this->belongsTo(Company::class); }
    public function assignments() { return $this->hasMany(MerchandisingTaskAssignment::class); }
}
