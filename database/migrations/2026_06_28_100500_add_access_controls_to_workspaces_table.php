<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            if (! Schema::hasColumn('workspaces', 'feature_flags')) {
                $table->json('feature_flags')->nullable()->after('document_settings');
            }

            if (! Schema::hasColumn('workspaces', 'plan_limits')) {
                $table->json('plan_limits')->nullable()->after('feature_flags');
            }

            if (! Schema::hasColumn('workspaces', 'access_override_ends_at')) {
                $table->timestamp('access_override_ends_at')->nullable()->after('is_suspended');
            }

            if (! Schema::hasColumn('workspaces', 'access_override_reason')) {
                $table->text('access_override_reason')->nullable()->after('access_override_ends_at');
            }

            if (! Schema::hasColumn('workspaces', 'access_override_granted_by')) {
                $table->foreignId('access_override_granted_by')->nullable()->after('access_override_reason')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            foreach (['feature_flags', 'plan_limits', 'access_override_ends_at', 'access_override_reason'] as $column) {
                if (Schema::hasColumn('workspaces', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('workspaces', 'access_override_granted_by')) {
                $table->dropConstrainedForeignId('access_override_granted_by');
            }
        });
    }
};
