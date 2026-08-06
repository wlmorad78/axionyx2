<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitor_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('sales_rep_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('competitor_id')->nullable()->constrained('competitors')->nullOnDelete();
            $table->enum('photo_type', ['PRICE_TAG', 'SHELF', 'DISPLAY', 'PROMOTION', 'NEW_PRODUCT']);
            $table->string('file_path');
            $table->dateTime('taken_at')->useCurrent();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitor_photos');
    }
};
