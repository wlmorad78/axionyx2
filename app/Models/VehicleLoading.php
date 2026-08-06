<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class VehicleLoading extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehicle_loadings';

    protected $fillable = [
        'vehicle_id',
        'load_request_id',
        'issue_order_id',
        'loading_date',
        'loaded_value',
    ];

    protected $casts = [
        'loading_date' => 'date',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function loadRequest()
    {
        return $this->belongsTo(LoadRequest::class);
    }

    public function issueOrder()
    {
        return $this->belongsTo(IssueOrder::class);
    }
}
