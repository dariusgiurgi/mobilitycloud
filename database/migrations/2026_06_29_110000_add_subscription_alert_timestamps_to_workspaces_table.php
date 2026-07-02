<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table): void {
            foreach ([
                'trial_ending_alerted_at',
                'trial_expired_alerted_at',
                'subscription_ending_alerted_at',
                'subscription_expired_alerted_at',
                'manual_access_ending_alerted_at',
                'demo_reset_stale_alerted_at',
            ] as $column) {
                if (! Schema::hasColumn('workspaces', $column)) {
                    $table->timestamp($column)->nullable()->after('billing_provider_subscription_id');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table): void {
            foreach ([
                'demo_reset_stale_alerted_at',
                'manual_access_ending_alerted_at',
                'subscription_expired_alerted_at',
                'subscription_ending_alerted_at',
                'trial_expired_alerted_at',
                'trial_ending_alerted_at',
            ] as $column) {
                if (Schema::hasColumn('workspaces', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
