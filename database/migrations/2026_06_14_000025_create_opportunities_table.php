<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->nullable()->constrained('leads');
            $table->string('opportunity_name', 255);
            $table->decimal('expected_value', 12, 2);
            $table->date('expected_close_date');
            $table->enum('stage', ['prospecting','qualification','proposal','negotiation','closed_won','closed_lost'])->default('prospecting');
            $table->enum('status', ['open','won','lost'])->default('open');
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('opportunities'); }
};
