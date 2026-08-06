<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_plan_id')->constrained()->cascadeOnDelete();
            $table->string('key'); // e.g. 'users', 'branches', 'warehouses', 'storage_gb', 'api_requests', 'companies'
            $table->string('value'); // e.g. '5', '10', '999' — stored as string, cast to int in model
            $table->timestamps();
            $table->unique(['subscription_plan_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_limits');
    }
};
