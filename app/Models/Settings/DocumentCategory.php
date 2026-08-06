<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class DocumentCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'document_categories';

    protected $fillable = [
        'name',
        'code',
    ];

    public function documents()
    {
        return $this->hasMany(Document::class);
    }
}
