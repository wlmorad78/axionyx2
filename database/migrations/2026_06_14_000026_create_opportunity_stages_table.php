<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('opportunity_stages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->integer('sequence')->default(0);
            $table->decimal('probability', 5, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('opportunity_stages'); }
};
