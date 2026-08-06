<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'provider_id',
        'external_document_no',
        'entity_type',
        'entity_id',
        'status',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function provider()
    {
        return $this->belongsTo(IntegrationProvider::class, 'provider_id');
    }

    public function logs()
    {
        return $this->hasMany(ExternalDocumentLog::class);
    }
}
