<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->after('id');
            $table->string('notification_no', 50)->nullable()->unique()->after('company_id');
            $table->foreignId('notification_type_id')->nullable()->constrained('notification_types')->after('notification_no');
            $table->foreignId('notification_event_id')->nullable()->constrained('notification_events')->after('notification_type_id');
            $table->string('priority', 20)->default('NORMAL')->after('notification_event_id');
            $table->string('status', 20)->default('PENDING')->after('priority');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['notification_type_id']);
            $table->dropForeign(['notification_event_id']);
            $table->dropColumn([
                'company_id',
                'notification_no',
                'notification_type_id',
                'notification_event_id',
                'priority',
                'status',
            ]);
        });
    }
};
