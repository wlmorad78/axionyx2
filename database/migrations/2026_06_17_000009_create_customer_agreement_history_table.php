<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_agreement_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_agreement_id')->constrained('customer_agreements');
            $table->string('action_type');
            $table->foreignId('action_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->dateTime('action_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_agreement_history');
    }
};
