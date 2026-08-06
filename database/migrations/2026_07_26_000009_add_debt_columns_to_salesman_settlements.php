<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('salesman_settlements', function (Blueprint $table) {
            $table->foreignId('salesman_account_id')->nullable()->after('issue_order_id')->constrained('salesman_accounts');
            $table->foreignId('open_debt_id')->nullable()->after('salesman_account_id')->constrained('salesman_debts')->comment('المديونية المفتوحة التي جرى تسويتها');
            $table->decimal('previous_debt', 12, 2)->default(0)->after('open_debt_id')->comment('المديونية السابقة قبل التسوية');
            $table->decimal('remaining_debt', 12, 2)->default(0)->after('previous_debt')->comment('المديونية المتبقية بعد التسوية');
        });
    }

    public function down(): void
    {
        Schema::table('salesman_settlements', function (Blueprint $table) {
            $table->dropColumn(['salesman_account_id', 'open_debt_id', 'previous_debt', 'remaining_debt']);
        });
    }
};