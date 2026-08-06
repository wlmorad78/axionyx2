<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('master_data_workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_data_workflow_id')->constrained('master_data_workflows');
            $table->integer('step_no');
            $table->foreignId('role_id')->nullable()->constrained('roles');
            $table->boolean('is_required')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('master_data_workflow_steps'); }
};
