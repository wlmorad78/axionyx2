<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('approval_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_request_id')->constrained('approval_requests');
            $table->foreignId('workflow_step_id')->nullable()->constrained('workflow_steps');
            $table->foreignId('user_id')->constrained('users');
            $table->enum('action', ['APPROVED','REJECTED','RETURNED','PENDING']);
            $table->text('notes')->nullable();
            $table->date('action_date');
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('approval_actions'); }
};
