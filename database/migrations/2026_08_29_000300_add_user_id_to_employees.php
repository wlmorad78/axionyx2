<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. إضافة user_id على جدولEmployees (idempotent) ──
        if (!Schema::hasColumn('employees', 'user_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            });
        }

        // ── 2. نسخ البيانات عبر البريد الإلكتروني ──
        DB::statement("
            UPDATE employees
            SET user_id = (
                SELECT u.id FROM users u WHERE u.email = employees.email
            )
            WHERE email IS NOT NULL AND email != ''
              AND user_id IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
