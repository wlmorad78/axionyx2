<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shelf_share_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shelf_share_survey_id')->constrained('shelf_share_surveys')->cascadeOnDelete();
            $table->string('brand_name');
            $table->integer('facings_count')->default(0);
            $table->decimal('shelf_percentage', 5, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shelf_share_items');
    }
};
