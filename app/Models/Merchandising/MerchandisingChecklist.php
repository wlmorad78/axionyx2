<?php

namespace App\Models\Merchandising;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Company;

class MerchandisingChecklist extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'merchandising_checklists';

    protected $fillable = [
        'company_id',
        'check_code',
        'check_name',
        'max_score',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function visitDetails()
    {
        return $this->hasMany(MerchandisingVisitDetail::class, 'checklist_id');
    }
}
