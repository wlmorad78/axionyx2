<?php

$base = dirname(__DIR__, 2);

$relationships = [
    'InventoryMovement' => <<<'PHP'

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
PHP,
    'Purchase' => <<<'PHP'

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }
PHP,
    'PurchaseItem' => <<<'PHP'

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
PHP,
    'Invoice' => <<<'PHP'

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function representative(): BelongsTo
    {
        return $this->belongsTo(Representative::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
PHP,
    'InvoiceItem' => <<<'PHP'

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
PHP,
    'SalesReturn' => <<<'PHP'

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesReturnItem::class);
    }
PHP,
    'SalesReturnItem' => <<<'PHP'

    public function salesReturn(): BelongsTo
    {
        return $this->belongsTo(SalesReturn::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
PHP,
    'CustomerPayment' => <<<'PHP'

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
PHP,
    'SupplierPayment' => <<<'PHP'

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
PHP,
    'Representative' => <<<'PHP'

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
PHP,
    'Visit' => <<<'PHP'

    public function representative(): BelongsTo
    {
        return $this->belongsTo(Representative::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
PHP,
    'CashTransaction' => <<<'PHP'

    public function cashbox(): BelongsTo
    {
        return $this->belongsTo(Cashbox::class);
    }
PHP,
    'BankTransaction' => <<<'PHP'

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }
PHP,
    'Notification' => <<<'PHP'

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
PHP,
    'ActivityLog' => <<<'PHP'

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
PHP,
];

foreach ($relationships as $model => $methods) {
    $path = "{$base}/app/Models/{$model}.php";
    $content = file_get_contents($path);
    $content = preg_replace('/\r?\n}\s*$/', "{$methods}\r\n}\r\n", $content);
    file_put_contents($path, $content);
}

echo "Relationships added.\n";
