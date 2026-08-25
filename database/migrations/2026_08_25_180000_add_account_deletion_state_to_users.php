<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('account_deletion_status', 32)->nullable()->after('archived_reason')->index();
            $table->timestamp('account_deletion_requested_at')->nullable()->after('account_deletion_status');
            $table->unsignedBigInteger('account_deletion_requested_by')->nullable()->after('account_deletion_requested_at');
            $table->string('account_deletion_project_disposition', 32)->nullable()->after('account_deletion_requested_by');
            $table->unsignedBigInteger('account_deletion_transfer_account_id')->nullable()->after('account_deletion_project_disposition');
            $table->timestamp('account_deletion_started_at')->nullable()->after('account_deletion_transfer_account_id');
            $table->text('account_deletion_failure')->nullable()->after('account_deletion_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['account_deletion_status']);
            $table->dropColumn([
                'account_deletion_status',
                'account_deletion_requested_at',
                'account_deletion_requested_by',
                'account_deletion_project_disposition',
                'account_deletion_transfer_account_id',
                'account_deletion_started_at',
                'account_deletion_failure',
            ]);
        });
    }
};
