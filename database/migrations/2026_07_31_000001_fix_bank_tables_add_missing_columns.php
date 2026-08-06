<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('bank_accounts', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            }
            if (!Schema::hasColumn('bank_accounts', 'account_id')) {
                $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            }
            if (!Schema::hasColumn('bank_accounts', 'account_name')) {
                $table->string('account_name')->nullable();
            }
            if (!Schema::hasColumn('bank_accounts', 'account_no')) {
                $table->string('account_no')->nullable();
            }
            if (!Schema::hasColumn('bank_accounts', 'swift_code')) {
                $table->string('swift_code')->nullable();
            }
            if (!Schema::hasColumn('bank_accounts', 'notes')) {
                $table->text('notes')->nullable();
            }
        });

        Schema::table('bank_transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('bank_transfers', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            }
            if (!Schema::hasColumn('bank_transfers', 'description')) {
                $table->text('description')->nullable();
            }
            if (!Schema::hasColumn('bank_transfers', 'status')) {
                $table->string('status')->default('completed');
            }
            if (!Schema::hasColumn('bank_transfers', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('employees')->nullOnDelete();
            }
            if (!Schema::hasColumn('bank_transfers', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->constrained('employees')->nullOnDelete();
            }
            if (!Schema::hasColumn('bank_transfers', 'approved_at')) {
                $table->timestamp('approved_at')->nullable();
            }
        });

        Schema::table('bank_reconciliations', function (Blueprint $table) {
            if (!Schema::hasColumn('bank_reconciliations', 'company_id')) {
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            }
            if (!Schema::hasColumn('bank_reconciliations', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            }
            if (!Schema::hasColumn('bank_reconciliations', 'book_balance')) {
                $table->decimal('book_balance', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('bank_reconciliations', 'reference')) {
                $table->string('reference')->nullable();
            }
            if (!Schema::hasColumn('bank_reconciliations', 'status')) {
                $table->string('status')->default('completed');
            }
            if (!Schema::hasColumn('bank_reconciliations', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('employees')->nullOnDelete();
            }
            if (!Schema::hasColumn('bank_reconciliations', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['account_id']);
            $table->dropColumn(['branch_id', 'account_id', 'account_name', 'account_no', 'swift_code', 'notes']);
        });

        Schema::table('bank_transfers', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['branch_id', 'description', 'status', 'created_by', 'approved_by', 'approved_at']);
        });

        Schema::table('bank_reconciliations', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['created_by']);
            $table->dropColumn(['company_id', 'branch_id', 'book_balance', 'reference', 'status', 'created_by', 'deleted_at']);
        });
    }
};
