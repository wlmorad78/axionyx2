<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('master_data_request_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_data_request_id')->constrained('master_data_requests');
            $table->foreignId('action_by')->constrained('users');
            $table->string('action_type');
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->text('notes')->nullable();
            $table->datetime('action_date');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('master_data_request_history'); }
};
