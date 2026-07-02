<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table): void {
            if (! Schema::hasColumn('workspaces', 'billing_interval')) {
                $table->string('billing_interval')->nullable()->after('billing_country');
            }

            if (! Schema::hasColumn('workspaces', 'billing_amount')) {
                $table->decimal('billing_amount', 12, 2)->nullable()->after('billing_interval');
            }

            if (! Schema::hasColumn('workspaces', 'billing_currency')) {
                $table->string('billing_currency', 3)->nullable()->after('billing_amount');
            }

            if (! Schema::hasColumn('workspaces', 'billing_reference')) {
                $table->string('billing_reference')->nullable()->after('billing_currency');
            }

            if (! Schema::hasColumn('workspaces', 'billing_provider')) {
                $table->string('billing_provider')->nullable()->after('billing_reference');
            }

            if (! Schema::hasColumn('workspaces', 'billing_provider_customer_id')) {
                $table->string('billing_provider_customer_id')->nullable()->after('billing_provider');
            }

            if (! Schema::hasColumn('workspaces', 'billing_provider_subscription_id')) {
                $table->string('billing_provider_subscription_id')->nullable()->after('billing_provider_customer_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table): void {
            foreach ([
                'billing_provider_subscription_id',
                'billing_provider_customer_id',
                'billing_provider',
                'billing_reference',
                'billing_currency',
                'billing_amount',
                'billing_interval',
            ] as $column) {
                if (Schema::hasColumn('workspaces', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
