<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspace_invitations', function (Blueprint $table): void {
            if (! Schema::hasColumn('workspace_invitations', 'project_id')) {
                $table->foreignId('project_id')
                    ->nullable()
                    ->after('workspace_id')
                    ->constrained()
                    ->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('workspace_invitations', function (Blueprint $table): void {
            if (Schema::hasColumn('workspace_invitations', 'project_id')) {
                $table->dropConstrainedForeignId('project_id');
            }
        });
    }
};
