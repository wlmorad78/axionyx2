<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('master_data_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->foreignId('request_type_id')->constrained('master_data_request_types');
            $table->string('request_no');
            $table->string('entity_type');
            $table->integer('entity_id')->nullable();
            $table->enum('request_action', ['CREATE','UPDATE','DELETE','ACTIVATE','DEACTIVATE']);
            $table->date('request_date');
            $table->foreignId('requested_by')->constrained('users');
            $table->enum('current_status', ['DRAFT','PENDING','APPROVED','REJECTED','RETURNED'])->default('DRAFT');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('master_data_requests'); }
};
