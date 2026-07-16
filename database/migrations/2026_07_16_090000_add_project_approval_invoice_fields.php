<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            if (! Schema::hasColumn('projects', 'approved_grant_amount')) {
                $table->decimal('approved_grant_amount', 15, 2)->nullable()->after('approved_budget');
            }

            if (! Schema::hasColumn('projects', 'approved_grant_currency')) {
                $table->string('approved_grant_currency', 3)->default('EUR')->after('approved_grant_amount');
            }

            if (! Schema::hasColumn('projects', 'approved_declared_at')) {
                $table->timestamp('approved_declared_at')->nullable()->after('approved_grant_currency');
            }

            if (! Schema::hasColumn('projects', 'approved_declared_by')) {
                $table->foreignId('approved_declared_by')->nullable()->after('approved_declared_at')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('projects', 'activation_fee_amount')) {
                $table->decimal('activation_fee_amount', 15, 2)->nullable()->after('approved_declared_by');
            }

            if (! Schema::hasColumn('projects', 'activation_fee_currency')) {
                $table->string('activation_fee_currency', 3)->default('EUR')->after('activation_fee_amount');
            }

            if (! Schema::hasColumn('projects', 'invoice_status')) {
                $table->string('invoice_status')->default('not_required')->after('activation_fee_currency');
            }

            if (! Schema::hasColumn('projects', 'invoice_number')) {
                $table->string('invoice_number')->nullable()->after('invoice_status');
            }

            if (! Schema::hasColumn('projects', 'invoice_sent_at')) {
                $table->timestamp('invoice_sent_at')->nullable()->after('invoice_number');
            }

            if (! Schema::hasColumn('projects', 'invoice_due_at')) {
                $table->timestamp('invoice_due_at')->nullable()->after('invoice_sent_at');
            }

            if (! Schema::hasColumn('projects', 'payment_confirmed_at')) {
                $table->timestamp('payment_confirmed_at')->nullable()->after('invoice_due_at');
            }

            if (! Schema::hasColumn('projects', 'payment_confirmed_by')) {
                $table->foreignId('payment_confirmed_by')->nullable()->after('payment_confirmed_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            foreach (['approved_declared_by', 'payment_confirmed_by'] as $column) {
                if (Schema::hasColumn('projects', $column)) {
                    try {
                        $table->dropForeign([$column]);
                    } catch (Throwable) {
                        //
                    }
                }
            }

            foreach ([
                'payment_confirmed_by',
                'payment_confirmed_at',
                'invoice_due_at',
                'invoice_sent_at',
                'invoice_number',
                'invoice_status',
                'activation_fee_currency',
                'activation_fee_amount',
                'approved_declared_by',
                'approved_declared_at',
                'approved_grant_currency',
                'approved_grant_amount',
            ] as $column) {
                if (Schema::hasColumn('projects', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
