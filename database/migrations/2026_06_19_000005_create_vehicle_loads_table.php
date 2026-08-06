<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('vehicle_loads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('load_request_id')->nullable()->constrained('load_requests')->nullOnDelete();
            $table->foreignId('issue_order_id')->nullable()->constrained('issue_orders')->nullOnDelete();
            $table->string('load_no', 50)->unique();
            $table->date('load_date');
            $table->decimal('loaded_value', 14, 4)->default(0);
            $table->decimal('loaded_qty', 12, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('vehicle_loads'); }
};
