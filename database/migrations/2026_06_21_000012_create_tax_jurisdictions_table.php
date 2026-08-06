<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('tax_jurisdictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();
            $table->string('jurisdiction_code', 50)->unique();
            $table->string('jurisdiction_name');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('tax_jurisdictions'); }
};
