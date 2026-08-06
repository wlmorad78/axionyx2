<?php

use App\Support\RoleNames;

return [

    /*
    |--------------------------------------------------------------------------
    | صلاحيات الموارد حسب الدور
    | * = كل العمليات | index, show, store, update, destroy, restore, force-delete
    | عمليات إضافية: submit, approve, confirm
    |--------------------------------------------------------------------------
    */
    'resources' => [
        RoleNames::ADMIN => ['*'],

        RoleNames::WAREHOUSE_KEEPER => [
            'warehouses' => ['*'],
            'product-stocks' => ['*'],
            'inventory-movements' => ['*'],
            'dispatch-orders' => ['*'],
            'dispatch-order-items' => ['*'],
            'rep-return-orders' => ['*'],
            'rep-return-order-items' => ['*'],
            'representative-stocks' => ['index', 'show'],
            'representative-stock-movements' => ['index', 'show'],
            'products' => ['index', 'show'],
            'categories' => ['index', 'show'],
            'sub-categories' => ['index', 'show'],
            'units' => ['index', 'show'],
            'product-prices' => ['index', 'show'],
            'representatives' => ['index', 'show'],
            'notifications' => ['index', 'show', 'update'],
        ],

        RoleNames::SALES_REP => [
            'customers' => ['index', 'show', 'store', 'update'],
            'invoices' => ['*'],
            'invoice-items' => ['*'],
            'sales-returns' => ['*'],
            'sales-return-items' => ['*'],
            'customer-payments' => ['index', 'show', 'store'],
            'visits' => ['*'],
            'dispatch-orders' => ['index', 'show', 'store', 'update', 'submit', 'dispatch'],
            'dispatch-order-items' => ['index', 'show', 'store', 'update', 'destroy'],
            'rep-return-orders' => ['index', 'show', 'store', 'update', 'submit'],
            'rep-return-order-items' => ['index', 'show', 'store', 'update', 'destroy'],
            'representative-stocks' => ['index', 'show'],
            'representative-stock-movements' => ['index', 'show'],
            'products' => ['index', 'show'],
            'categories' => ['index', 'show'],
            'sub-categories' => ['index', 'show'],
            'units' => ['index', 'show'],
            'product-prices' => ['index', 'show'],
            'governorates' => ['index', 'show'],
            'districts' => ['index', 'show'],
            'areas' => ['index', 'show'],
            'streets' => ['index', 'show'],
            'sales-territories' => ['index', 'show'],
            'representatives' => ['show'],
            'notifications' => ['index', 'show', 'update'],
        ],

        RoleNames::ACCOUNTANT => [
            'invoices' => ['index', 'show'],
            'invoice-items' => ['index', 'show'],
            'customer-payments' => ['*'],
            'supplier-payments' => ['*'],
            'purchases' => ['*'],
            'purchase-items' => ['*'],
            'suppliers' => ['*'],
            'customers' => ['index', 'show'],
            'cashboxes' => ['*'],
            'cash-transactions' => ['*'],
            'banks' => ['*'],
            'bank-transactions' => ['*'],
            'products' => ['index', 'show'],
            'categories' => ['index', 'show'],
            'sub-categories' => ['index', 'show'],
            'units' => ['index', 'show'],
            'product-prices' => ['index', 'show'],
            'notifications' => ['index', 'show', 'update'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | تقييد بيانات المندوب (عمود representative_id أو user_id)
    |--------------------------------------------------------------------------
    */
    'representative_scopes' => [
        'customers' => 'representative_id',
        'invoices' => 'representative_id',
        'visits' => 'representative_id',
        'dispatch-orders' => 'representative_id',
        'rep-return-orders' => 'representative_id',
        'representative-stocks' => 'representative_id',
        'representative-stock-movements' => 'representative_id',
        'notifications' => 'user_id',
    ],

    'representative_self' => [
        'representatives' => 'id',
    ],

    'parent_scopes' => [
        'invoice-items' => ['relation' => 'invoice', 'column' => 'representative_id'],
        'sales-returns' => ['relation' => 'invoice', 'column' => 'representative_id'],
        'sales-return-items' => ['relation' => 'salesReturn.invoice', 'column' => 'representative_id'],
        'dispatch-order-items' => ['relation' => 'dispatchOrder', 'column' => 'representative_id'],
        'rep-return-order-items' => ['relation' => 'repReturnOrder', 'column' => 'representative_id'],
    ],

];
