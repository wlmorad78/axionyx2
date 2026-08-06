<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('campaign_code');
            $table->string('campaign_name');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('budget', 12, 2)->default(0);
            $table->enum('status', ['DRAFT', 'ACTIVE', 'COMPLETED', 'CANCELLED'])->default('DRAFT');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'campaign_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_campaigns');
    }
};
