<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'billing_interval')) {
                $table->string('billing_interval')->nullable()->after('document_settings');
            }

            if (! Schema::hasColumn('users', 'billing_amount')) {
                $table->decimal('billing_amount', 12, 2)->nullable()->after('billing_interval');
            }

            if (! Schema::hasColumn('users', 'billing_currency')) {
                $table->string('billing_currency', 3)->nullable()->after('billing_amount');
            }

            if (! Schema::hasColumn('users', 'billing_reference')) {
                $table->string('billing_reference')->nullable()->after('billing_currency');
            }

            if (! Schema::hasColumn('users', 'billing_provider')) {
                $table->string('billing_provider')->nullable()->after('billing_reference');
            }

            if (! Schema::hasColumn('users', 'billing_provider_customer_id')) {
                $table->string('billing_provider_customer_id')->nullable()->after('billing_provider');
            }

            if (! Schema::hasColumn('users', 'billing_provider_subscription_id')) {
                $table->string('billing_provider_subscription_id')->nullable()->after('billing_provider_customer_id');
            }

            if (! Schema::hasColumn('users', 'access_override_granted_by')) {
                $table->foreignId('access_override_granted_by')->nullable()->after('access_override_reason')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('users', 'demo_reset_frequency')) {
                $table->string('demo_reset_frequency')->default('manual')->after('access_override_granted_by');
            }

            if (! Schema::hasColumn('users', 'demo_last_reset_at')) {
                $table->timestamp('demo_last_reset_at')->nullable()->after('demo_reset_frequency');
            }

            foreach ([
                'trial_ending_alerted_at',
                'trial_expired_alerted_at',
                'subscription_ending_alerted_at',
                'subscription_expired_alerted_at',
                'manual_access_ending_alerted_at',
                'demo_reset_stale_alerted_at',
            ] as $column) {
                if (! Schema::hasColumn('users', $column)) {
                    $table->timestamp($column)->nullable()->after('demo_last_reset_at');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            foreach ([
                'demo_reset_stale_alerted_at',
                'manual_access_ending_alerted_at',
                'subscription_expired_alerted_at',
                'subscription_ending_alerted_at',
                'trial_expired_alerted_at',
                'trial_ending_alerted_at',
                'demo_last_reset_at',
                'demo_reset_frequency',
                'billing_provider_subscription_id',
                'billing_provider_customer_id',
                'billing_provider',
                'billing_reference',
                'billing_currency',
                'billing_amount',
                'billing_interval',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('users', 'access_override_granted_by')) {
                $table->dropConstrainedForeignId('access_override_granted_by');
            }
        });
    }
};
