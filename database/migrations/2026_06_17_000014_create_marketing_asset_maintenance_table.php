<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_asset_maintenance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_asset_id')->constrained('marketing_assets')->onDelete('cascade');
            $table->date('maintenance_date');
            $table->string('maintenance_type');
            $table->decimal('cost', 12, 2)->default(0);
            $table->string('vendor_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_asset_maintenance');
    }
};
