<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_rule_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_rule_id')->constrained('notification_rules')->cascadeOnDelete();
            $table->string('recipient_type', 20);
            $table->string('recipient_value', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_rule_recipients');
    }
};
