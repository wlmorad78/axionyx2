<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workflow_steps', function (Blueprint $table) {
            $table->foreignId('workflow_id')->nullable()->after('workflow_definition_id')->constrained('workflows')->nullOnDelete();
            $table->boolean('allow_delegate')->default(false)->after('role_id');
            $table->dropColumn('is_required');
            $table->boolean('is_mandatory')->default(true)->after('step_name');
        });
    }

    public function down(): void
    {
        Schema::table('workflow_steps', function (Blueprint $table) {
            $table->dropForeign(['workflow_id']);
            $table->dropColumn('workflow_id');
            $table->dropColumn('allow_delegate');
            $table->boolean('is_required')->default(true);
            $table->dropColumn('is_mandatory');
        });
    }
};
