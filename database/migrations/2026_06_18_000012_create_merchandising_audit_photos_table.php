<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('merchandising_audit_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchandising_audit_id')->constrained('merchandising_audits')->cascadeOnDelete();
            $table->enum('photo_type', ['STORE', 'SHELF', 'REFRIGERATOR', 'DISPLAY', 'POSM']);
            $table->string('file_path');
            $table->timestamp('taken_at')->useCurrent();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('merchandising_audit_photos'); }
};
