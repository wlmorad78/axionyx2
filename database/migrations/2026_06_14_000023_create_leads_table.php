<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('lead_code', 50)->unique();
            $table->string('lead_name', 255);
            $table->string('mobile', 50)->nullable();
            $table->string('email', 255)->nullable();
            $table->enum('source', ['website','referral','cold_call','social','other'])->default('other');
            $table->enum('status', ['new','contacted','qualified','converted','lost'])->default('new');
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('leads'); }
};
