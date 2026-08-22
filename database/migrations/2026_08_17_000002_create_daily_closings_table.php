<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_closings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            // القطاع: inventory (المخازن) | finance (المالية)
            $table->string('sector', 20)->index();
            $table->date('closing_date')->index();
            $table->string('status', 20)->default('closed'); // closed | reopened
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'sector', 'closing_date'], 'daily_closing_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_closings');
    }
};
