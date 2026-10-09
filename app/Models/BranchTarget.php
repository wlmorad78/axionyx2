<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToCompany;

class BranchTarget extends Model
{
    use BelongsToCompany;

    protected $table = 'branch_targets';

    protected $fillable = [
        'company_id',
        'branch_id',
        'year',
        'month',
        'target_amount',
    ];

    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
