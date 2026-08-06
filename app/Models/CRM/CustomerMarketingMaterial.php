<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Merchandising\MarketingMaterial;

class CustomerMarketingMaterial extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'customer_marketing_materials';

    protected $fillable = [
        'customer_id',
        'marketing_material_id',
        'distribution_date',
        'qty',
        'notes',
    ];

    protected $casts = [
        'distribution_date' => 'date',
        'qty' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function marketingMaterial()
    {
        return $this->belongsTo(MarketingMaterial::class, 'marketing_material_id');
    }
}
