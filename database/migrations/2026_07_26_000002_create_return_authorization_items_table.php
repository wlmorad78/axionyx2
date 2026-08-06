<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('return_authorization_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_authorization_id')->constrained('return_authorizations')->cascadeOnDelete();
            $table->foreignId('sales_invoice_id')->nullable()->constrained('sales_invoices');
            $table->foreignId('sales_invoice_item_id')->nullable()->constrained('sales_invoice_items');
            $table->foreignId('item_id')->constrained('items');
            $table->foreignId('unit_id')->nullable()->constrained('units');
            $table->decimal('qty', 12, 2)->default(0)->comment('الكمية المرتجعة');
            $table->decimal('price', 12, 2)->default(0)->comment('سعر البيع');
            $table->decimal('gross_amount', 12, 2)->default(0)->comment('الإجمالي قبل الخصم والضريبة');
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0)->comment('صافي قيمة الصنف بعد الاقتطاعات');
            $table->string('acceptance_status', 20)->default('pending')->comment('pending, accepted, rejected');
            $table->text('acceptance_notes')->nullable()->comment('ملاحظات فحص أمين المخزن');
            $table->unsignedBigInteger('rejected_by')->nullable()->comment('أمين المخزن الذي رفض');
            $table->timestamp('accepted_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_authorization_items');
    }
};