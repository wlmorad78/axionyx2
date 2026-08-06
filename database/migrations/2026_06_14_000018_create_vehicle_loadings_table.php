<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('vehicle_loadings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles');
            $table->unsignedBigInteger('load_request_id')->nullable();
            $table->unsignedBigInteger('issue_order_id')->nullable();
            $table->date('loading_date');
            $table->decimal('loaded_value', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('load_request_id')->references('id')->on('load_requests')->nullOnDelete();
            $table->foreign('issue_order_id')->references('id')->on('issue_orders')->nullOnDelete();
        });
    }
    public function down(): void { Schema::dropIfExists('vehicle_loadings'); }
};
