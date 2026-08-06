<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('pricing_rule_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pricing_rule_id')->constrained('pricing_rules')->cascadeOnDelete();
            $table->string('condition_type');
            $table->string('condition_value');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('pricing_rule_conditions'); }
};
