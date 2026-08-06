<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── sales_invoice_items ──
        Schema::table('sales_invoice_items', function (Blueprint $table) {
            $table->decimal('conversion_factor', 12, 4)->default(1)->after('unit_id');
            $table->decimal('base_quantity', 12, 2)->default(0)->after('qty');
        });

        // ── purchase_invoice_items ──
        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            $table->decimal('conversion_factor', 12, 4)->default(1)->after('unit_id');
            $table->decimal('base_quantity', 12, 2)->default(0)->after('qty');
        });

        // ── load_request_items ──
        Schema::table('load_request_items', function (Blueprint $table) {
            $table->decimal('conversion_factor', 12, 4)->default(1)->after('unit_id');
            $table->decimal('base_quantity', 12, 2)->default(0)->after('quantity');
        });

        // ── issue_order_items ── add unit_id + conversion_factor + base_quantity
        Schema::table('issue_order_items', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->constrained('units')->after('item_id');
            $table->decimal('conversion_factor', 12, 4)->default(1)->after('unit_id');
            $table->decimal('base_quantity', 12, 2)->default(0)->after('issued_quantity');
        });

        // ── inventory_transaction_items ── add conversion_factor
        Schema::table('inventory_transaction_items', function (Blueprint $table) {
            $table->decimal('conversion_factor', 12, 4)->default(1)->after('unit_id');
        });

        // ── stock_adjustment_items ──
        if (Schema::hasTable('stock_adjustment_items')) {
            Schema::table('stock_adjustment_items', function (Blueprint $table) {
                if (!Schema::hasColumn('stock_adjustment_items', 'conversion_factor')) {
                    $table->decimal('conversion_factor', 12, 4)->default(1);
                }
                if (!Schema::hasColumn('stock_adjustment_items', 'base_quantity')) {
                    $table->decimal('base_quantity', 12, 2)->default(0);
                }
            });
        }

        // ── stock_count_items ──
        if (Schema::hasTable('stock_count_items')) {
            Schema::table('stock_count_items', function (Blueprint $table) {
                if (!Schema::hasColumn('stock_count_items', 'conversion_factor')) {
                    $table->decimal('conversion_factor', 12, 4)->default(1);
                }
                if (!Schema::hasColumn('stock_count_items', 'base_quantity')) {
                    $table->decimal('base_quantity', 12, 2)->default(0);
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('sales_invoice_items', function (Blueprint $table) {
            $table->dropColumn(['conversion_factor', 'base_quantity']);
        });

        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            $table->dropColumn(['conversion_factor', 'base_quantity']);
        });

        Schema::table('load_request_items', function (Blueprint $table) {
            $table->dropColumn(['conversion_factor', 'base_quantity']);
        });

        Schema::table('issue_order_items', function (Blueprint $table) {
            $table->dropColumn(['unit_id', 'conversion_factor', 'base_quantity']);
        });

        Schema::table('inventory_transaction_items', function (Blueprint $table) {
            $table->dropColumn('conversion_factor');
        });

        if (Schema::hasTable('stock_adjustment_items')) {
            Schema::table('stock_adjustment_items', function (Blueprint $table) {
                $table->dropColumn(['conversion_factor', 'base_quantity']);
            });
        }

        if (Schema::hasTable('stock_count_items')) {
            Schema::table('stock_count_items', function (Blueprint $table) {
                $table->dropColumn(['conversion_factor', 'base_quantity']);
            });
        }
    }
};
