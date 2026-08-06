<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_marketing_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_asset_id')->constrained('marketing_assets')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('agreement_id')->nullable()->constrained('customer_agreements')->onDelete('set null');
            $table->date('assigned_date');
            $table->date('expected_return_date')->nullable();
            $table->date('actual_return_date')->nullable();
            $table->enum('status', ['ASSIGNED', 'RETURNED', 'LOST', 'DAMAGED'])->default('ASSIGNED');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_marketing_assets');
    }
};
