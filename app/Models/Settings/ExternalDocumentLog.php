<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalDocumentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_document_id',
        'request_payload',
        'response_payload',
        'status',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
    ];

    public function document()
    {
        return $this->belongsTo(ExternalDocument::class, 'external_document_id');
    }
}
