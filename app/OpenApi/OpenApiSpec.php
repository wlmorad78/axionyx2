<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Axionyx ERP API',
    description: 'واجهة برمجة نظام إدارة موارد الشركة - شركة توزيع'
)]
#[OA\Server(url: L5_SWAGGER_CONST_HOST, description: 'API Server')]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum Token',
    description: 'أدخل التوكن: Bearer {token}'
)]
#[OA\Tag(name: 'Auth', description: 'المصادقة')]
#[OA\Tag(name: 'Distribution', description: 'التوزيع - صرف وارتجاع ومخزون المندوب')]
#[OA\Tag(name: 'Sales', description: 'المبيعات والفواتير')]
#[OA\Tag(name: 'Inventory', description: 'المخزون')]
#[OA\Tag(name: 'Master Data', description: 'البيانات الأساسية')]
#[OA\Schema(
    schema: 'User',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'usercode', type: 'integer', example: 100001),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'email', type: 'string', nullable: true),
        new OA\Property(property: 'phone', type: 'string', nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean'),
        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(ref: '#/components/schemas/Role')),
        new OA\Property(property: 'representative', ref: '#/components/schemas/Representative', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'Role',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'Representative',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'user_id', type: 'integer'),
        new OA\Property(property: 'sales_territory_id', type: 'integer'),
        new OA\Property(property: 'target_amount', type: 'number'),
        new OA\Property(property: 'commission_rate', type: 'number'),
    ]
)]
#[OA\Schema(
    schema: 'Pagination',
    properties: [
        new OA\Property(property: 'current_page', type: 'integer'),
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
        new OA\Property(property: 'per_page', type: 'integer'),
        new OA\Property(property: 'total', type: 'integer'),
    ]
)]
#[OA\Schema(
    schema: 'Error',
    properties: [
        new OA\Property(property: 'message', type: 'string'),
        new OA\Property(property: 'errors', type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'array', items: new OA\Items(type: 'string'))),
    ]
)]
class OpenApiSpec
{
}
