<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'plan')) {
                $table->string('plan')->default('free')->after('role');
            }
            if (! Schema::hasColumn('users', 'subscription_status')) {
                $table->string('subscription_status')->default('active')->after('plan');
            }
            if (! Schema::hasColumn('users', 'trial_ends_at')) {
                $table->timestamp('trial_ends_at')->nullable()->after('subscription_status');
            }
            if (! Schema::hasColumn('users', 'subscription_ends_at')) {
                $table->timestamp('subscription_ends_at')->nullable()->after('trial_ends_at');
            }
            if (! Schema::hasColumn('users', 'feature_flags')) {
                $table->json('feature_flags')->nullable()->after('subscription_ends_at');
            }
            if (! Schema::hasColumn('users', 'plan_limits')) {
                $table->json('plan_limits')->nullable()->after('feature_flags');
            }
            if (! Schema::hasColumn('users', 'currencies')) {
                $table->json('currencies')->nullable()->after('plan_limits');
            }
            if (! Schema::hasColumn('users', 'document_settings')) {
                $table->json('document_settings')->nullable()->after('currencies');
            }
            if (! Schema::hasColumn('users', 'access_override_ends_at')) {
                $table->timestamp('access_override_ends_at')->nullable()->after('document_settings');
            }
            if (! Schema::hasColumn('users', 'access_override_reason')) {
                $table->string('access_override_reason')->nullable()->after('access_override_ends_at');
            }
        });

        Schema::table('projects', function (Blueprint $table): void {
            if (! Schema::hasColumn('projects', 'owner_id')) {
                $table->foreignId('owner_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            if (Schema::hasColumn('projects', 'owner_id')) {
                $table->dropConstrainedForeignId('owner_id');
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            foreach ([
                'access_override_reason',
                'access_override_ends_at',
                'document_settings',
                'currencies',
                'plan_limits',
                'feature_flags',
                'subscription_ends_at',
                'trial_ends_at',
                'subscription_status',
                'plan',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
