<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('price_approval_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_approval_request_id')->constrained('price_approval_requests')->cascadeOnDelete();
            $table->integer('step_no')->default(1);
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('PENDING');
            $table->datetime('action_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('price_approval_steps'); }
};
