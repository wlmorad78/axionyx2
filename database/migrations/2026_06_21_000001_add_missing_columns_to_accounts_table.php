<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->foreignId('account_type_id')->nullable()->after('company_id')->constrained('account_types')->nullOnDelete();
            $table->string('description')->nullable()->after('account_name');
            $table->string('normal_balance', 20)->nullable()->after('allow_transactions')->default('debit');
            $table->decimal('opening_balance', 15, 2)->nullable()->after('normal_balance')->default(0);
            $table->decimal('current_balance', 15, 2)->nullable()->after('opening_balance')->default(0);
            $table->text('notes')->nullable()->after('current_balance');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropForeign(['account_type_id']);
            $table->dropColumn([
                'account_type_id', 'description', 'normal_balance',
                'opening_balance', 'current_balance', 'notes'
            ]);
        });
    }
};
