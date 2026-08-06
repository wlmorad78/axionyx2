<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_subscriptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('subscription_plan_id')
                  ->constrained();

            $table->foreignId('payment_method_id')
                  ->nullable()
                  ->constrained();

            $table->date('start_date');

            $table->date('end_date');

            $table->date('trial_end_date')->nullable();

            $table->decimal('amount', 12, 2);

            $table->string('status', 20)->default('active');

            $table->string('payment_reference')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_subscriptions');
    }
};
