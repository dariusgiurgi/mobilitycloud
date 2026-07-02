<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes()->after('remember_token');
            }

            if (! Schema::hasColumn('users', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('deleted_at');
            }

            if (! Schema::hasColumn('users', 'archived_by')) {
                $table->foreignId('archived_by')->nullable()->after('archived_at')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('users', 'archived_reason')) {
                $table->text('archived_reason')->nullable()->after('archived_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'deleted_at')) {
                $table->dropSoftDeletes();
            }

            foreach (['archived_reason', 'archived_by', 'archived_at'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
