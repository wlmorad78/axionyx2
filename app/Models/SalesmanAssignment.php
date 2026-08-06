<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesmanAssignment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'sales_territory_id',
        'job_role',
        'parent_assignment_id',
        'start_date',
        'end_date',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function salesTerritory(): BelongsTo
    {
        return $this->belongsTo(SalesTerritory::class);
    }

    public function parentAssignment(): BelongsTo
    {
        return $this->belongsTo(SalesmanAssignment::class, 'parent_assignment_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(SalesmanAssignment::class, 'parent_assignment_id');
    }
}
