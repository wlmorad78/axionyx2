<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_queue', function (Blueprint $table) {
            $table->foreignId('notification_id')->nullable()->constrained('notifications')->after('id');
            $table->foreignId('channel_id')->nullable()->constrained('notification_channels')->after('notification_id');
            $table->integer('attempt_count')->default(0)->after('sent_at');
            $table->timestamp('last_attempt_at')->nullable()->after('attempt_count');
        });
    }

    public function down(): void
    {
        Schema::table('notification_queue', function (Blueprint $table) {
            $table->dropForeign(['notification_id']);
            $table->dropForeign(['channel_id']);
            $table->dropColumn([
                'notification_id',
                'channel_id',
                'attempt_count',
                'last_attempt_at',
            ]);
        });
    }
};
