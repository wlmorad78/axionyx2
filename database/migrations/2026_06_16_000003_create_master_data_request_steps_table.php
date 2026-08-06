<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('master_data_request_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_data_request_id')->constrained('master_data_requests');
            $table->integer('step_no');
            $table->foreignId('role_id')->nullable()->constrained('roles');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->enum('status', ['PENDING','APPROVED','REJECTED','RETURNED'])->default('PENDING');
            $table->datetime('action_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('master_data_request_steps'); }
};
