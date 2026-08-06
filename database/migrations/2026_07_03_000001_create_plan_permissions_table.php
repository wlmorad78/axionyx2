<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_plan_id')->constrained()->cascadeOnDelete();
            $table->string('permission_code'); // e.g. 'sales.invoice.view' or 'sales.*' or '*'
            $table->timestamps();
            $table->unique(['subscription_plan_id', 'permission_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_permissions');
    }
};
