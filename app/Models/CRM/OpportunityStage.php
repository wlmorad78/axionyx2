<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class OpportunityStage extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'opportunity_stages';

    protected $fillable = [
        'name',
        'sequence',
        'probability',
    ];
}
