<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_invitations', function (Blueprint $table): void {
            if (! Schema::hasColumn('project_invitations', 'project_id')) {
                return;
            }

            $table->index(['project_id', 'email'], 'project_invitations_project_email_index');
        });
    }

    public function down(): void
    {
        Schema::table('project_invitations', function (Blueprint $table): void {
            $table->dropIndex('project_invitations_project_email_index');
        });
    }
};
