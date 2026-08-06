<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MerchandisingPhoto extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'merchandising_photos';

    protected $fillable = [
        'merchandising_visit_id',
        'photo_type',
        'file_path',
        'taken_at',
    ];

    protected $casts = [
        'taken_at' => 'datetime',
    ];

    public function visit()
    {
        return $this->belongsTo(MerchandisingVisit::class, 'merchandising_visit_id');
    }
}
