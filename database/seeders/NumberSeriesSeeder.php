<?php

use App\Models\Company;
use App\Models\NumberSeries;

$types = [
    'sales_invoice' => 'SINV',
    'purchase_invoice' => 'PINV',
    'payment_voucher' => 'PV',
    'receipt_voucher' => 'RV',
    'opening_balance' => 'OB',
];

Company::all()->each(function ($c) use ($types) {
    foreach ($types as $type => $prefix) {
        NumberSeries::firstOrCreate(
            ['company_id' => $c->id, 'document_type' => $type],
            [
                'prefix' => $prefix,
                'format' => '{prefix}-{sequence}',
                'next_sequence' => 1,
                'padding' => 5,
            ]
        );
    }
});

echo "Seeded number_series for " . Company::count() . " companies\n";
