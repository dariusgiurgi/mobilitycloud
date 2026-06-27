<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'is_suspended')) {
                $table->boolean('is_suspended')->default(false)->after('role');
            }

            if (! Schema::hasColumn('users', 'must_change_password')) {
                $table->boolean('must_change_password')->default(false)->after('is_suspended');
            }

            if (! Schema::hasColumn('users', 'support_notes')) {
                $table->text('support_notes')->nullable()->after('notification_preferences');
            }

            if (! Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('support_notes');
            }
        });

        Schema::table('workspaces', function (Blueprint $table): void {
            if (! Schema::hasColumn('workspaces', 'subscription_status')) {
                $table->string('subscription_status')->default('active')->after('plan');
            }

            if (! Schema::hasColumn('workspaces', 'subscription_ends_at')) {
                $table->timestamp('subscription_ends_at')->nullable()->after('trial_ends_at');
            }

            if (! Schema::hasColumn('workspaces', 'is_suspended')) {
                $table->boolean('is_suspended')->default(false)->after('subscription_ends_at');
            }

            if (! Schema::hasColumn('workspaces', 'is_internal')) {
                $table->boolean('is_internal')->default(false)->after('is_suspended');
            }

            if (! Schema::hasColumn('workspaces', 'internal_notes')) {
                $table->text('internal_notes')->nullable()->after('is_internal');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            foreach (['is_suspended', 'must_change_password', 'support_notes', 'last_login_at'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('workspaces', function (Blueprint $table): void {
            foreach (['subscription_status', 'subscription_ends_at', 'is_suspended', 'is_internal', 'internal_notes'] as $column) {
                if (Schema::hasColumn('workspaces', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
