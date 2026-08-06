<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('driver_licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies');
            $table->foreignId('driver_id')->constrained('drivers');
            $table->string('license_type', 50);
            $table->string('license_number', 100);
            $table->date('issue_date');
            $table->date('expiry_date');
            $table->string('issuing_authority', 255)->nullable();
            $table->string('file_path', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_licenses');
    }
};
