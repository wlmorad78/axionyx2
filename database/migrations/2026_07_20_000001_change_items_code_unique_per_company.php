<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. items: code + barcode becomes per-company unique
        Schema::table('items', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->unique(['company_id', 'code']);
            $table->dropUnique(['barcode']);
            $table->unique(['company_id', 'barcode']);
        });

        // 2. item_barcodes: barcode becomes per-item unique (not global)
        Schema::table('item_barcodes', function (Blueprint $table) {
            $table->dropUnique(['barcode']);
            $table->unique(['item_id', 'barcode']);
        });
    }

    public function down(): void
    {
        Schema::table('item_barcodes', function (Blueprint $table) {
            $table->dropUnique(['item_id', 'barcode']);
            $table->unique(['barcode']);
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'barcode']);
            $table->unique(['barcode']);
            $table->dropUnique(['company_id', 'code']);
            $table->unique(['code']);
        });
    }
};
