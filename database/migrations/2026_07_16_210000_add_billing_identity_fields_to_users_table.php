<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'billing_name')) {
                $table->string('billing_name')->nullable()->after('billing_provider_subscription_id');
            }

            if (! Schema::hasColumn('users', 'billing_vat')) {
                $table->string('billing_vat')->nullable()->after('billing_name');
            }

            if (! Schema::hasColumn('users', 'billing_country')) {
                $table->string('billing_country')->nullable()->after('billing_vat');
            }

            if (! Schema::hasColumn('users', 'billing_address')) {
                $table->text('billing_address')->nullable()->after('billing_country');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            foreach ([
                'billing_address',
                'billing_country',
                'billing_vat',
                'billing_name',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
