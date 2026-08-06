<?php
namespace App\Models\Accounting;
use Illuminate\Database\Eloquent\Model;

class AccountType extends Model {
    protected $fillable = ['code','name','nature'];
}
