<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_templates', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->after('id');
            $table->foreignId('notification_type_id')->nullable()->constrained('notification_types')->after('company_id');
            $table->foreignId('channel_id')->nullable()->constrained('notification_channels')->after('channel');
            $table->string('language_code', 10)->default('ar')->after('is_active');
            $table->string('subject', 255)->nullable()->before('title');
        });
    }

    public function down(): void
    {
        Schema::table('notification_templates', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['notification_type_id']);
            $table->dropForeign(['channel_id']);
            $table->dropColumn([
                'company_id',
                'notification_type_id',
                'channel_id',
                'language_code',
                'subject',
            ]);
        });
    }
};
