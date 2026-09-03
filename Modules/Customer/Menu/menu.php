<?php

return [
    'key' => 'customer',
    'label' => 'العملاء',
    'label_en' => 'Customers',
    'icon' => 'users',
    'items' => [
        [
            'key' => 'customer.list',
            'label' => 'قائمة العملاء',
            'label_en' => 'Customer List',
            'route' => 'customer.index',
            'icon' => 'list',
            'permission' => 'customer.view',
        ],
        [
            'key' => 'customer.accounts',
            'label' => 'حسابات العملاء',
            'label_en' => 'Customer Accounts',
            'route' => 'customer.accounts',
            'icon' => 'wallet',
            'permission' => 'customer.accounts',
        ],
        [
            'key' => 'customer.import',
            'label' => 'استيراد',
            'label_en' => 'Import',
            'route' => 'customer.import',
            'icon' => 'upload',
            'permission' => 'customer.import',
        ],
        [
            'key' => 'customer.export',
            'label' => 'تصدير',
            'label_en' => 'Export',
            'route' => 'customer.export',
            'icon' => 'download',
            'permission' => 'customer.export',
        ],
    ],
];
