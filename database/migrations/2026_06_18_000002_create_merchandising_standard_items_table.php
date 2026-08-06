<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('merchandising_standard_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchandising_standard_id')->constrained('merchandising_standards')->cascadeOnDelete();
            $table->integer('item_no');
            $table->string('item_name');
            $table->integer('score')->default(0);
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('merchandising_standard_items'); }
};
