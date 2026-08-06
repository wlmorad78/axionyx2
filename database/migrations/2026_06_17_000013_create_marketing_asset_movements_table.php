<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_asset_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_asset_id')->constrained('marketing_assets')->onDelete('cascade');
            $table->date('movement_date');
            $table->enum('movement_type', ['ASSIGN', 'RETURN', 'TRANSFER', 'MAINTENANCE', 'SCRAP']);
            $table->foreignId('from_customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->foreignId('to_customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_asset_movements');
    }
};
