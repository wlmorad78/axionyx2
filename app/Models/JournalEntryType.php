<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class JournalEntryType extends Model {
    protected $fillable = ['code','name','is_system'];
    protected $casts = ['is_system'=>'boolean'];
}
