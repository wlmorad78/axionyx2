<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesIncentive extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'code', 'name_ar', 'name_en', 'promotion_type',
        'description', 'valid_from', 'valid_to', 'priority', 'is_active', 'notes',
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_to' => 'date',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function conditions() { return $this->hasMany(SalesIncentiveCondition::class); }
    public function rewards() { return $this->hasMany(SalesIncentiveReward::class); }
    public function invoiceIncentives() { return $this->hasMany(SalesInvoiceIncentive::class); }
}
