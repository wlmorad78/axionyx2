<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisplayLocation extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'location_code', 'location_name', 'description'];
    protected $table = 'display_locations';

    public function company() { return $this->belongsTo(Company::class); }
    public function shelfAudits() { return $this->hasMany(ShelfAudit::class); }
}
