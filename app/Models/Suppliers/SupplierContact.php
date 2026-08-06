<?php
namespace App\Models\Suppliers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierContact extends Model {
    use SoftDeletes;
    protected $fillable = ['supplier_id','contact_name','job_title','mobile','phone','email','is_default'];
    protected $casts = ['is_default'=>'boolean'];
    public function supplier() { return $this->belongsTo(Supplier::class); }
}
