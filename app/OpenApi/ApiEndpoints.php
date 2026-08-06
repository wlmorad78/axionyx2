<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * توثيق نقاط النهاية الرئيسية للموارد.
 * كل مورد يدعم: index, store, show, update, destroy, restore, force-delete
 */
class ApiEndpoints
{
    #[OA\Get(path: '/health-check', summary: 'فحص حالة السيرفر', tags: ['Auth'], responses: [
        new OA\Response(response: 200, description: 'السيرفر يعمل'),
    ])]
    public function healthCheck(): void {}

    // --- Distribution ---

    #[OA\Get(path: '/dispatch-orders', summary: 'قائمة أوامر الصرف', security: [['sanctum' => []]], tags: ['Distribution'], parameters: [
        new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'trashed', in: 'query', schema: new OA\Schema(type: 'boolean')),
    ], responses: [new OA\Response(response: 200, description: 'نجاح', content: new OA\JsonContent(ref: '#/components/schemas/Pagination'))])]
    public function dispatchOrdersIndex(): void {}

    #[OA\Post(path: '/dispatch-orders', summary: 'إنشاء أمر صرف (تحميل للمندوب)', security: [['sanctum' => []]], tags: ['Distribution'], requestBody: new OA\RequestBody(content: new OA\JsonContent(
        required: ['order_no', 'warehouse_id', 'representative_id', 'order_date', 'items'],
        properties: [
            new OA\Property(property: 'order_no', type: 'string', example: 'DO-001'),
            new OA\Property(property: 'warehouse_id', type: 'integer'),
            new OA\Property(property: 'representative_id', type: 'integer'),
            new OA\Property(property: 'order_date', type: 'string', format: 'date'),
            new OA\Property(property: 'status', type: 'string', enum: ['draft', 'pending', 'approved', 'cancelled']),
            new OA\Property(property: 'notes', type: 'string'),
            new OA\Property(property: 'items', type: 'array', items: new OA\Items(properties: [
                new OA\Property(property: 'product_id', type: 'integer'),
                new OA\Property(property: 'quantity', type: 'number'),
                new OA\Property(property: 'unit_cost', type: 'number'),
            ])),
        ]
    )), responses: [new OA\Response(response: 201, description: 'تم الإنشاء')])]
    public function dispatchOrdersStore(): void {}

    #[OA\Post(path: '/dispatch-orders/{id}/approve', summary: 'اعتماد أمر الصرف - خصم المخزن وإضافة للمندوب', security: [['sanctum' => []]], tags: ['Distribution'], parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ], requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
        new OA\Property(property: 'approved_by', type: 'integer'),
    ])), responses: [new OA\Response(response: 200, description: 'تم الاعتماد')])]
    public function dispatchOrdersApprove(): void {}

    #[OA\Post(path: '/rep-return-orders', summary: 'إنشاء أمر ارتجاع للمخزن', security: [['sanctum' => []]], tags: ['Distribution'], requestBody: new OA\RequestBody(content: new OA\JsonContent(
        required: ['order_no', 'warehouse_id', 'representative_id', 'return_date', 'items'],
        properties: [
            new OA\Property(property: 'order_no', type: 'string', example: 'RR-001'),
            new OA\Property(property: 'warehouse_id', type: 'integer'),
            new OA\Property(property: 'representative_id', type: 'integer'),
            new OA\Property(property: 'return_date', type: 'string', format: 'date'),
            new OA\Property(property: 'items', type: 'array', items: new OA\Items(properties: [
                new OA\Property(property: 'product_id', type: 'integer'),
                new OA\Property(property: 'quantity', type: 'number'),
            ])),
        ]
    )), responses: [new OA\Response(response: 201, description: 'تم الإنشاء')])]
    public function repReturnOrdersStore(): void {}

    #[OA\Post(path: '/rep-return-orders/{id}/approve', summary: 'اعتماد الارتجاع - إرجاع البضاعة للمخزن', security: [['sanctum' => []]], tags: ['Distribution'], parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ], responses: [new OA\Response(response: 200, description: 'تم الاعتماد')])]
    public function repReturnOrdersApprove(): void {}

    #[OA\Get(path: '/representative-stocks', summary: 'مخزون المندوب', security: [['sanctum' => []]], tags: ['Distribution'], parameters: [
        new OA\Parameter(name: 'representative_id', in: 'query', schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'product_id', in: 'query', schema: new OA\Schema(type: 'integer')),
    ], responses: [new OA\Response(response: 200, description: 'نجاح')])]
    public function representativeStocksIndex(): void {}

    // --- Sales ---

    #[OA\Post(path: '/invoices', summary: 'إنشاء فاتورة بيع (يخصم من مخزون المندوب)', security: [['sanctum' => []]], tags: ['Sales'], requestBody: new OA\RequestBody(content: new OA\JsonContent(
        required: ['invoice_no', 'customer_id', 'invoice_date', 'items'],
        properties: [
            new OA\Property(property: 'invoice_no', type: 'string'),
            new OA\Property(property: 'customer_id', type: 'integer'),
            new OA\Property(property: 'representative_id', type: 'integer'),
            new OA\Property(property: 'invoice_date', type: 'string', format: 'date'),
            new OA\Property(property: 'status', type: 'string', enum: ['draft', 'paid', 'partial', 'unpaid', 'cancelled']),
            new OA\Property(property: 'total', type: 'number'),
            new OA\Property(property: 'items', type: 'array', items: new OA\Items(properties: [
                new OA\Property(property: 'product_id', type: 'integer'),
                new OA\Property(property: 'quantity', type: 'number'),
                new OA\Property(property: 'price', type: 'number'),
                new OA\Property(property: 'total', type: 'number'),
            ])),
        ]
    )), responses: [new OA\Response(response: 201, description: 'تم الإنشاء')])]
    public function invoicesStore(): void {}

    #[OA\Post(path: '/invoices/{id}/confirm', summary: 'تأكيد فاتورة مسودة وخصم المخزون', security: [['sanctum' => []]], tags: ['Sales'], parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ], responses: [new OA\Response(response: 200, description: 'تم التأكيد')])]
    public function invoicesConfirm(): void {}

    // --- Master Data (examples) ---

    #[OA\Get(path: '/products', summary: 'قائمة المنتجات', security: [['sanctum' => []]], tags: ['Master Data'], parameters: [
        new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
    ], responses: [new OA\Response(response: 200, description: 'نجاح')])]
    public function productsIndex(): void {}

    #[OA\Get(path: '/customers', summary: 'قائمة العملاء', security: [['sanctum' => []]], tags: ['Master Data'], responses: [new OA\Response(response: 200, description: 'نجاح')])]
    public function customersIndex(): void {}

    #[OA\Get(path: '/warehouses', summary: 'قائمة المخازن', security: [['sanctum' => []]], tags: ['Inventory'], responses: [new OA\Response(response: 200, description: 'نجاح')])]
    public function warehousesIndex(): void {}

    #[OA\Get(path: '/product-stocks', summary: 'أرصدة المخزن', security: [['sanctum' => []]], tags: ['Inventory'], responses: [new OA\Response(response: 200, description: 'نجاح')])]
    public function productStocksIndex(): void {}
}
