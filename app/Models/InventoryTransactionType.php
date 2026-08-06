<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class InventoryTransactionType extends Model {
    protected $fillable = ['code','name','effect','is_active'];
    protected $casts = ['is_active'=>'boolean'];
}
