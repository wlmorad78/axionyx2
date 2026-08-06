<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_event_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_account_id')->constrained('integration_accounts');
            $table->foreignId('integration_event_id')->constrained('integration_events');
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_event_subscriptions');
    }
};
