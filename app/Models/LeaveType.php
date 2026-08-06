<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveType extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['code', 'name_ar', 'name_en', 'default_days', 'is_paid', 'is_active', 'notes'];
    protected function casts(): array { return ['is_paid' => 'boolean', 'is_active' => 'boolean']; }
}
