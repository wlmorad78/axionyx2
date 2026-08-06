<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('return_authorizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('salesman_id')->constrained('employees');
            $table->foreignId('salesman_account_id')->nullable()->constrained('salesman_accounts');
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('sales_route_id')->nullable()->constrained('routes');
            $table->string('return_auth_no', 50)->unique();
            $table->date('return_date');
            $table->time('return_time')->nullable();
            $table->decimal('total_sales_value', 12, 2)->default(0)->comment('إجمالي قيمة المبيعات المرتبطة بالإذن');
            $table->decimal('total_return_value', 12, 2)->default(0)->comment('قيمة المرتجعات المقبولة');
            $table->decimal('net_debt_amount', 12, 2)->default(0)->comment('صافي المطلوب من المندوب = المبيعات - المرتجعات');
            $table->unsignedBigInteger('return_reason_id')->nullable();
            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('employees');
            $table->foreignId('approved_by')->nullable()->constrained('employees');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_authorizations');
    }
};