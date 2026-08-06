<?php
namespace App\Models\Merchandising;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Company;

class MerchandisingStandard extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'standard_code', 'standard_name', 'description', 'max_score', 'is_active'];
    protected $casts = ['is_active' => 'boolean', 'max_score' => 'integer'];

    public function company() { return $this->belongsTo(Company::class); }
    public function items() { return $this->hasMany(MerchandisingStandardItem::class); }
}
