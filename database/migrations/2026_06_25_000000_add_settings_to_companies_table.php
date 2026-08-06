<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Financial settings
            $table->decimal('tax_rate', 5, 2)->nullable()->after('is_active');
            $table->string('default_bank')->nullable()->after('tax_rate');
            $table->string('default_treasury')->nullable()->after('default_bank');

            // Sales settings
            $table->string('default_price_list')->nullable()->after('default_treasury');
            $table->integer('default_price_level')->nullable()->after('default_price_list');
            $table->decimal('max_discount', 5, 2)->nullable()->after('default_price_level');
            $table->decimal('max_credit', 10, 2)->nullable()->after('max_discount');

            // Inventory settings
            $table->string('default_warehouse')->nullable()->after('max_credit');
            $table->boolean('low_stock_alert')->default(false)->after('default_warehouse');
            $table->integer('min_stock')->nullable()->after('low_stock_alert');
            $table->string('default_vehicle')->nullable()->after('min_stock');

            // HR settings
            $table->time('work_start')->nullable()->after('default_vehicle');
            $table->time('work_end')->nullable()->after('work_start');
            $table->integer('late_grace')->nullable()->after('work_end');
            $table->string('salary_currency', 3)->nullable()->after('late_grace');
            $table->integer('week_start')->default(0)->after('salary_currency');

            // Notification settings
            $table->boolean('sales_notifications')->default(true)->after('week_start');
            $table->boolean('stock_alerts')->default(true)->after('sales_notifications');
            $table->boolean('customer_notifications')->default(true)->after('stock_alerts');
            $table->boolean('email_notifications')->default(true)->after('customer_notifications');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'tax_rate', 'default_bank', 'default_treasury',
                'default_price_list', 'default_price_level', 'max_discount', 'max_credit',
                'default_warehouse', 'low_stock_alert', 'min_stock', 'default_vehicle',
                'work_start', 'work_end', 'late_grace', 'salary_currency', 'week_start',
                'sales_notifications', 'stock_alerts', 'customer_notifications', 'email_notifications',
            ]);
        });
    }
};
