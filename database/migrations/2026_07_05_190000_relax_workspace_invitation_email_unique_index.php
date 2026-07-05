<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspace_invitations', function (Blueprint $table): void {
            $table->dropUnique('workspace_invitations_workspace_id_email_unique');
            $table->index(['workspace_id', 'email', 'project_id'], 'workspace_invitations_project_email_index');
        });
    }

    public function down(): void
    {
        Schema::table('workspace_invitations', function (Blueprint $table): void {
            $table->dropIndex('workspace_invitations_project_email_index');
            $table->unique(['workspace_id', 'email']);
        });
    }
};
