<?php

namespace App\Models\Merchandising;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MerchandisingVisitDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'merchandising_visit_details';

    protected $fillable = [
        'merchandising_visit_id',
        'checklist_id',
        'score',
        'remarks',
    ];

    protected $casts = [
        'score' => 'decimal:2',
    ];

    public function visit()
    {
        return $this->belongsTo(MerchandisingVisit::class, 'merchandising_visit_id');
    }

    public function checklist()
    {
        return $this->belongsTo(MerchandisingChecklist::class, 'checklist_id');
    }
}
