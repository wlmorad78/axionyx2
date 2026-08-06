<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_classes', function (Blueprint $table) {
            $table->dropColumn(['credit_limit', 'discount_percentage', 'priority_level']);
        });
    }

    public function down(): void
    {
        Schema::table('customer_classes', function (Blueprint $table) {
            $table->decimal('credit_limit', 12, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->integer('priority_level')->default(0);
        });
    }
};
