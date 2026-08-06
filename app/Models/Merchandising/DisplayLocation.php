<?php
namespace App\Models\Merchandising;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Company;

class DisplayLocation extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'location_code', 'location_name', 'description'];
    protected $table = 'display_locations';

    public function company() { return $this->belongsTo(Company::class); }
    public function shelfAudits() { return $this->hasMany(ShelfAudit::class); }
}
