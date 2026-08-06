<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EInvoiceProvider extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'e_invoice_providers';

    protected $fillable = [
        'provider_name',
        'provider_type',
    ];

    protected $casts = [];

    public function transactions()
    {
        return $this->hasMany(EInvoiceTransaction::class, 'provider_id');
    }
}
