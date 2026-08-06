<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class CustomerAgreementHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_agreement_id',
        'action_type',
        'action_by',
        'notes',
        'action_date',
    ];

    protected function casts(): array
    {
        return [
            'action_date' => 'datetime',
        ];
    }

    public function customerAgreement()
    {
        return $this->belongsTo(CustomerAgreement::class);
    }

    public function actionBy()
    {
        return $this->belongsTo(User::class, 'action_by');
    }
}
