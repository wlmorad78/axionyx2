<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('treasuries', function (Blueprint $table) {
            $table->foreignId('treasury_type_id')->nullable()->after('branch_id')->constrained()->nullOnDelete();
            $table->foreignId('currency_id')->nullable()->after('treasury_type_id')->constrained()->nullOnDelete();
            $table->string('name_ar')->nullable()->after('code');
            $table->string('name_en')->nullable()->after('name_ar');
            $table->text('notes')->nullable()->after('opening_balance');
            $table->boolean('is_main')->default(false)->after('notes');
            $table->string('name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('treasuries', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->dropColumn(['treasury_type_id', 'currency_id', 'name_ar', 'name_en', 'notes', 'is_main']);
        });
    }
};
