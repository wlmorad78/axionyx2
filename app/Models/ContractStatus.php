<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContractStatus extends Model {
    use HasFactory;
    protected $fillable = ['code', 'name_ar', 'name_en', 'color', 'is_system', 'is_active', 'notes'];
    protected function casts(): array { return ['is_system' => 'boolean', 'is_active' => 'boolean']; }
}
