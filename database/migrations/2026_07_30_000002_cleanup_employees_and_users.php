<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // حذف كل الموظفين عدا اللي مرتبطين بـ user 1
        DB::table('employees')->where('user_id', '!=', 1)->delete();
        DB::table('employees')->whereNull('user_id')->delete();

        // حذف كل المستخدمين عدا user 1
        DB::table('users')->where('id', '!=', 1)->delete();

        // حذف علاقات الأدوار للمستخدمين المحذوفين
        DB::table('user_roles')->where('user_id', '!=', 1)->delete();
    }

    public function down(): void
    {
        // لا يمكن التراجع عن الحذف
    }
};
