<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('workspace_invitations') && ! Schema::hasTable('project_invitations')) {
            Schema::rename('workspace_invitations', 'project_invitations');
        }

        $fallbackOwnerId = DB::table('users')->min('id');

        if ($fallbackOwnerId && Schema::hasTable('projects') && Schema::hasColumn('projects', 'owner_id')) {
            DB::table('projects')
                ->whereNull('owner_id')
                ->update(['owner_id' => $fallbackOwnerId]);
        }

        if (Schema::hasTable('content_blocks')
            && Schema::hasColumn('content_blocks', 'owner_id')
            && $fallbackOwnerId) {
            DB::table('content_blocks')
                ->whereNull('owner_id')
                ->update(['owner_id' => $fallbackOwnerId]);
        }

        if (Schema::hasTable('project_invitations')) {
            if (! Schema::hasColumn('project_invitations', 'project_id')) {
                Schema::table('project_invitations', function (Blueprint $table): void {
                    $table->foreignId('project_id')
                        ->nullable()
                        ->after('id')
                        ->constrained()
                        ->cascadeOnDelete();
                });
            }

            $this->dropIndexIfExists('project_invitations', 'project_invitations_workspace_id_email_unique');
            $this->dropIndexIfExists('project_invitations', 'project_invitations_workspace_id_accepted_at_index');
            $this->dropIndexIfExists('project_invitations', 'project_invitations_project_email_index');
        }

        $this->dropColumnIfExists('users', 'current_workspace_id');
        $this->dropColumnIfExists('projects', 'workspace_id');
        $this->dropColumnIfExists('content_blocks', 'workspace_id');
        $this->dropColumnIfExists('public_content_blocks', 'origin_workspace_id');
        $this->dropColumnIfExists('saved_calculations', 'workspace_id');
        $this->dropColumnIfExists('project_activity_logs', 'workspace_id');
        $this->dropColumnIfExists('project_invitations', 'workspace_id');
        $this->dropColumnIfExists('platform_announcements', 'workspace_ids');

        Schema::dropIfExists('platform_workspace_notes');
        Schema::dropIfExists('platform_subscription_events');
        Schema::dropIfExists('workspace_user');
        Schema::dropIfExists('workspaces');
    }

    public function down(): void
    {
        // Workspace architecture was removed intentionally. Re-introducing it
        // would require a product-level rollback, not an automatic migration.
    }

    private function dropColumnIfExists(string $tableName, string $column): void
    {
        if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, $column)) {
            return;
        }

        $this->dropForeignIfExists($tableName, $column);

        Schema::table($tableName, function (Blueprint $table) use ($column): void {
            $table->dropColumn($column);
        });
    }

    private function dropForeignIfExists(string $tableName, string $column): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        try {
            Schema::table($tableName, function (Blueprint $table) use ($column): void {
                $table->dropForeign([$column]);
            });
        } catch (Throwable) {
            //
        }
    }

    private function dropIndexIfExists(string $tableName, string $indexName): void
    {
        if (! Schema::hasTable($tableName) || DB::getDriverName() === 'sqlite') {
            return;
        }

        try {
            Schema::table($tableName, function (Blueprint $table) use ($indexName): void {
                $table->dropIndex($indexName);
            });
        } catch (Throwable) {
            //
        }
    }
};
