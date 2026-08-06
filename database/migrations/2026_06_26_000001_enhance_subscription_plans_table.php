<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->unsignedTinyInteger('tier')->after('code')->default(1);
            $table->string('package_name')->after('tier')->nullable();
            $table->unsignedInteger('max_users')->after('max_treasuries')->default(1);
            $table->decimal('monthly_price', 12, 2)->after('price')->default(0);
            $table->decimal('setup_price', 12, 2)->after('monthly_price')->default(0);
            $table->text('description')->after('setup_price')->nullable();
            $table->json('features')->after('description')->nullable();
            $table->boolean('is_popular')->after('is_active')->default(false);
            $table->unsignedInteger('sort_order')->after('is_popular')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn([
                'tier', 'package_name', 'max_users', 'monthly_price',
                'setup_price', 'description', 'features', 'is_popular', 'sort_order',
            ]);
        });
    }
};
