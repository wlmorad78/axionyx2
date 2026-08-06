<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_agreement_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_agreement_id')->constrained('customer_agreements');
            $table->date('period_from');
            $table->date('period_to');
            $table->decimal('target_amount', 12, 2);
            $table->decimal('achieved_amount', 12, 2)->default(0);
            $table->decimal('achievement_percent', 5, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_agreement_targets');
    }
};
