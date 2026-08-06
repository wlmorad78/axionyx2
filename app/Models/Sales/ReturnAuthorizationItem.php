<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\HR\Employee;
use App\Models\Inventory\Item;
use App\Models\Inventory\Unit;

class ReturnAuthorizationItem extends Model
{
    use SoftDeletes;

    protected $table = 'return_authorization_items';

    protected $fillable = [
        'return_authorization_id', 'sales_invoice_id', 'sales_invoice_item_id',
        'item_id', 'unit_id', 'qty', 'price', 'gross_amount',
        'discount_amount', 'tax_amount', 'net_amount',
        'acceptance_status', 'acceptance_notes', 'rejected_by',
        'accepted_at', 'notes',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'price' => 'decimal:2',
        'gross_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'accepted_at' => 'datetime',
    ];

    public function returnAuthorization(): BelongsTo { return $this->belongsTo(ReturnAuthorization::class); }
    public function salesInvoice(): BelongsTo { return $this->belongsTo(SalesInvoice::class); }
    public function salesInvoiceItem(): BelongsTo { return $this->belongsTo(SalesInvoiceItem::class); }
    public function item(): BelongsTo { return $this->belongsTo(Item::class); }
    public function unit(): BelongsTo { return $this->belongsTo(Unit::class); }
    public function rejectedByEmployee(): BelongsTo { return $this->belongsTo(Employee::class, 'rejected_by'); }
}