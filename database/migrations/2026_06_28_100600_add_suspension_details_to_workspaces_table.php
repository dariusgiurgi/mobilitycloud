<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            if (! Schema::hasColumn('workspaces', 'suspension_category')) {
                $table->string('suspension_category')->nullable()->after('is_suspended');
            }

            if (! Schema::hasColumn('workspaces', 'suspension_reason')) {
                $table->text('suspension_reason')->nullable()->after('suspension_category');
            }

            if (! Schema::hasColumn('workspaces', 'suspended_at')) {
                $table->timestamp('suspended_at')->nullable()->after('suspension_reason');
            }

            if (! Schema::hasColumn('workspaces', 'suspended_by')) {
                $table->foreignId('suspended_by')->nullable()->after('suspended_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            foreach (['suspension_category', 'suspension_reason', 'suspended_at'] as $column) {
                if (Schema::hasColumn('workspaces', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('workspaces', 'suspended_by')) {
                $table->dropConstrainedForeignId('suspended_by');
            }
        });
    }
};
