<?php

$base = dirname(__DIR__, 2);
$controllers = [
    'User' => ['resource' => 'user', 'searchable' => ['name', 'email', 'phone']],
    'Role' => ['resource' => 'role', 'searchable' => ['name']],
    'UserRole' => ['resource' => 'user_role', 'searchable' => [], 'with' => ['user', 'role']],
    'Region' => ['resource' => 'region', 'searchable' => ['name'], 'with' => ['parent']],
    'Representative' => ['resource' => 'representative', 'searchable' => [], 'with' => ['user', 'region']],
    'Customer' => ['resource' => 'customer', 'searchable' => ['code', 'name', 'phone', 'email'], 'with' => ['region', 'representative']],
    'Supplier' => ['resource' => 'supplier', 'searchable' => ['code', 'name', 'phone', 'email']],
    'Category' => ['resource' => 'category', 'searchable' => ['name'], 'with' => ['parent']],
    'Product' => ['resource' => 'product', 'searchable' => ['code', 'barcode', 'name'], 'with' => ['category']],
    'Warehouse' => ['resource' => 'warehouse', 'searchable' => ['name'], 'with' => ['manager']],
    'ProductStock' => ['resource' => 'product_stock', 'searchable' => [], 'with' => ['warehouse', 'product']],
    'InventoryMovement' => ['resource' => 'inventory_movement', 'searchable' => [], 'with' => ['product', 'warehouse', 'creator']],
    'Purchase' => ['resource' => 'purchase', 'searchable' => ['invoice_no'], 'with' => ['supplier', 'creator', 'items']],
    'PurchaseItem' => ['resource' => 'purchase_item', 'searchable' => [], 'with' => ['purchase', 'product']],
    'Invoice' => ['resource' => 'invoice', 'searchable' => ['invoice_no'], 'with' => ['customer', 'representative', 'creator', 'items']],
    'InvoiceItem' => ['resource' => 'invoice_item', 'searchable' => [], 'with' => ['invoice', 'product']],
    'SalesReturn' => ['resource' => 'sales_return', 'searchable' => [], 'with' => ['invoice', 'customer', 'items']],
    'SalesReturnItem' => ['resource' => 'sales_return_item', 'searchable' => [], 'with' => ['salesReturn', 'product']],
    'CustomerPayment' => ['resource' => 'customer_payment', 'searchable' => [], 'with' => ['customer', 'invoice', 'creator']],
    'SupplierPayment' => ['resource' => 'supplier_payment', 'searchable' => [], 'with' => ['supplier', 'purchase', 'creator']],
    'Visit' => ['resource' => 'visit', 'searchable' => [], 'with' => ['representative', 'customer']],
    'Cashbox' => ['resource' => 'cashbox', 'searchable' => ['name']],
    'CashTransaction' => ['resource' => 'cash_transaction', 'searchable' => [], 'with' => ['cashbox']],
    'Bank' => ['resource' => 'bank', 'searchable' => ['name', 'account_number']],
    'BankTransaction' => ['resource' => 'bank_transaction', 'searchable' => [], 'with' => ['bank']],
    'Notification' => ['resource' => 'notification', 'searchable' => ['title'], 'with' => ['user']],
    'ActivityLog' => ['resource' => 'activity_log', 'searchable' => ['action', 'table_name'], 'with' => ['user']],
];

foreach ($controllers as $name => $config) {
    $with = var_export($config['with'] ?? [], true);
    $searchable = var_export($config['searchable'] ?? [], true);
    $resource = $config['resource'];

    $content = <<<PHP
<?php

namespace App\Http\Controllers\Api;

use App\Models\\{$name};
use App\Support\ValidationRules;
use Illuminate\Database\Eloquent\Model;

class {$name}Controller extends BaseApiController
{
    protected string \$modelClass = {$name}::class;

    protected array \$with = {$with};

    protected array \$searchable = {$searchable};

    protected function rules(string \$action, ?Model \$model = null): array
    {
        return ValidationRules::for('{$resource}', \$action, \$model);
    }
}

PHP;

    file_put_contents("{$base}/app/Http/Controllers/Api/{$name}Controller.php", $content);
}

$models = [
    'InventoryMovement' => ['fillable' => ['product_id', 'warehouse_id', 'movement_type', 'reference_type', 'reference_id', 'quantity', 'unit_cost', 'movement_date', 'created_by'], 'casts' => ['quantity' => 'decimal:2', 'unit_cost' => 'decimal:2', 'movement_date' => 'date']],
    'Purchase' => ['fillable' => ['invoice_no', 'supplier_id', 'purchase_date', 'subtotal', 'discount', 'tax', 'total', 'status', 'notes', 'created_by'], 'casts' => ['purchase_date' => 'date', 'subtotal' => 'decimal:2', 'discount' => 'decimal:2', 'tax' => 'decimal:2', 'total' => 'decimal:2']],
    'PurchaseItem' => ['fillable' => ['purchase_id', 'product_id', 'quantity', 'price', 'discount', 'total'], 'casts' => ['quantity' => 'decimal:2', 'price' => 'decimal:2', 'discount' => 'decimal:2', 'total' => 'decimal:2']],
    'Invoice' => ['fillable' => ['invoice_no', 'customer_id', 'representative_id', 'invoice_date', 'subtotal', 'discount', 'tax', 'total', 'paid_amount', 'remaining_amount', 'status', 'notes', 'created_by'], 'casts' => ['invoice_date' => 'date', 'subtotal' => 'decimal:2', 'discount' => 'decimal:2', 'tax' => 'decimal:2', 'total' => 'decimal:2', 'paid_amount' => 'decimal:2', 'remaining_amount' => 'decimal:2']],
    'InvoiceItem' => ['fillable' => ['invoice_id', 'product_id', 'quantity', 'price', 'discount', 'total'], 'casts' => ['quantity' => 'decimal:2', 'price' => 'decimal:2', 'discount' => 'decimal:2', 'total' => 'decimal:2']],
    'SalesReturn' => ['fillable' => ['invoice_id', 'customer_id', 'return_date', 'total_amount', 'notes'], 'casts' => ['return_date' => 'date', 'total_amount' => 'decimal:2']],
    'SalesReturnItem' => ['fillable' => ['sales_return_id', 'product_id', 'quantity', 'price', 'total'], 'casts' => ['quantity' => 'decimal:2', 'price' => 'decimal:2', 'total' => 'decimal:2']],
    'CustomerPayment' => ['fillable' => ['customer_id', 'invoice_id', 'payment_date', 'amount', 'payment_method', 'notes', 'created_by'], 'casts' => ['payment_date' => 'date', 'amount' => 'decimal:2']],
    'SupplierPayment' => ['fillable' => ['supplier_id', 'purchase_id', 'payment_date', 'amount', 'payment_method', 'notes', 'created_by'], 'casts' => ['payment_date' => 'date', 'amount' => 'decimal:2']],
    'Representative' => ['fillable' => ['user_id', 'region_id', 'target_amount', 'commission_rate'], 'casts' => ['target_amount' => 'decimal:2', 'commission_rate' => 'decimal:2']],
    'Visit' => ['fillable' => ['representative_id', 'customer_id', 'visit_date', 'check_in', 'check_out', 'latitude', 'longitude', 'notes'], 'casts' => ['visit_date' => 'date', 'latitude' => 'decimal:7', 'longitude' => 'decimal:7']],
    'Cashbox' => ['fillable' => ['name', 'balance'], 'casts' => ['balance' => 'decimal:2']],
    'CashTransaction' => ['fillable' => ['cashbox_id', 'transaction_type', 'amount', 'reference_type', 'reference_id', 'transaction_date', 'notes'], 'casts' => ['amount' => 'decimal:2', 'transaction_date' => 'date']],
    'Bank' => ['fillable' => ['name', 'account_number', 'iban', 'balance'], 'casts' => ['balance' => 'decimal:2']],
    'BankTransaction' => ['fillable' => ['bank_id', 'transaction_type', 'amount', 'reference_type', 'reference_id', 'transaction_date', 'notes'], 'casts' => ['amount' => 'decimal:2', 'transaction_date' => 'date']],
    'Notification' => ['fillable' => ['user_id', 'title', 'message', 'is_read'], 'casts' => ['is_read' => 'boolean']],
    'ActivityLog' => ['fillable' => ['user_id', 'action', 'table_name', 'record_id', 'old_values', 'new_values'], 'casts' => ['old_values' => 'array', 'new_values' => 'array']],
];

foreach ($models as $name => $config) {
    $fillable = var_export($config['fillable'], true);
    $casts = var_export($config['casts'], true);

    $content = <<<PHP
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class {$name} extends Model
{
    use SoftDeletes;

    protected \$fillable = {$fillable};

    protected function casts(): array
    {
        return {$casts};
    }
}

PHP;

    file_put_contents("{$base}/app/Models/{$name}.php", $content);
}

echo "Generated controllers and models.\n";
