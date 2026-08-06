<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * 1. Add uuid column if it does not already exist.
         */
        if (!Schema::hasColumn('sales_invoices', 'uuid')) {
            Schema::table('sales_invoices', function (Blueprint $table) {
                $table->string('uuid')->nullable();
            });
        }

        /*
         * 2. Add offline/sync columns only if they do not already exist.
         */
        $columns = [
            'temp_invoice_no',
            'source',
            'mode',
            'device_id',
            'sync_status',
            'synced_at',
            'number_series_id',
        ];

        $columnsToAdd = [];

        foreach ($columns as $column) {
            if (!Schema::hasColumn('sales_invoices', $column)) {
                $columnsToAdd[] = $column;
            }
        }

        if (!empty($columnsToAdd)) {
            Schema::table('sales_invoices', function (Blueprint $table) use ($columnsToAdd) {
                if (in_array('temp_invoice_no', $columnsToAdd)) {
                    $table->string('temp_invoice_no', 50)->nullable();
                }

                if (in_array('source', $columnsToAdd)) {
                    $table->string('source', 20)->default('desktop');
                }

                if (in_array('mode', $columnsToAdd)) {
                    $table->string('mode', 20)->nullable();
                }

                if (in_array('device_id', $columnsToAdd)) {
                    $table->string('device_id', 20)->nullable();
                }

                if (in_array('sync_status', $columnsToAdd)) {
                    $table->string('sync_status', 20)->default('synced');
                }

                if (in_array('synced_at', $columnsToAdd)) {
                    $table->timestamp('synced_at')->nullable();
                }

                if (in_array('number_series_id', $columnsToAdd)) {
                    $table->unsignedBigInteger('number_series_id')->nullable();
                }
            });
        }

        /*
         * 3. Generate UUIDs for existing invoices.
         *
         * PostgreSQL:
         * Uses gen_random_uuid().
         *
         * SQLite:
         * Uses Laravel Str::uuid().
         */
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "UPDATE sales_invoices
                 SET uuid = gen_random_uuid()
                 WHERE uuid IS NULL"
            );
        } else {
            $invoices = DB::table('sales_invoices')
                ->whereNull('uuid')
                ->pluck('id');

            foreach ($invoices as $id) {
                DB::table('sales_invoices')
                    ->where('id', $id)
                    ->update([
                        'uuid' => (string) Str::uuid(),
                    ]);
            }
        }

        /*
         * 4. Add unique index to UUID only if it does not already exist.
         */
        try {
            Schema::table('sales_invoices', function (Blueprint $table) {
                $table->unique('uuid', 'sales_invoices_uuid_unique');
            });
        } catch (\Throwable $e) {
            // Ignore if the unique index already exists.
        }
    }

    public function down(): void
    {
        /*
         * Drop unique UUID index if it exists.
         */
        try {
            Schema::table('sales_invoices', function (Blueprint $table) {
                $table->dropUnique('sales_invoices_uuid_unique');
            });
        } catch (\Throwable $e) {
            // Ignore if the index does not exist.
        }

        /*
         * Drop columns only if they exist.
         */
        $columns = [
            'uuid',
            'temp_invoice_no',
            'source',
            'mode',
            'device_id',
            'sync_status',
            'synced_at',
            'number_series_id',
        ];

        $columnsToDrop = [];

        foreach ($columns as $column) {
            if (Schema::hasColumn('sales_invoices', $column)) {
                $columnsToDrop[] = $column;
            }
        }

        if (!empty($columnsToDrop)) {
            Schema::table('sales_invoices', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }
    }
};