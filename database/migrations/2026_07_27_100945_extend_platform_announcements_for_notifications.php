<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_announcements', function (Blueprint $table): void {
            $table->string('delivery_type')->default('banner')->after('severity');
            $table->json('target_user_ids')->nullable()->after('plans');
            $table->timestamp('notification_sent_at')->nullable()->after('is_dismissible');
            $table->unsignedInteger('notification_sent_count')->default(0)->after('notification_sent_at');

            $table->index('delivery_type');
            $table->index('notification_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('platform_announcements', function (Blueprint $table): void {
            $table->dropIndex(['delivery_type']);
            $table->dropIndex(['notification_sent_at']);
            $table->dropColumn([
                'delivery_type',
                'target_user_ids',
                'notification_sent_at',
                'notification_sent_count',
            ]);
        });
    }
};
