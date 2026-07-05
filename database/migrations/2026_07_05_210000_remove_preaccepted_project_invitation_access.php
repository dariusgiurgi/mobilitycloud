<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('workspace_invitations')
            ->select('workspace_invitations.project_id', 'users.id as user_id')
            ->join('users', function ($join): void {
                $join->whereRaw('LOWER(users.email) = LOWER(workspace_invitations.email)');
            })
            ->whereNotNull('workspace_invitations.project_id')
            ->whereNull('workspace_invitations.accepted_at')
            ->where('workspace_invitations.role', 'like', 'project_%')
            ->orderBy('workspace_invitations.id')
            ->chunk(100, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('project_user')
                        ->where('project_id', $row->project_id)
                        ->where('user_id', $row->user_id)
                        ->delete();
                }
            });
    }

    public function down(): void
    {
        // This migration intentionally does not recreate pre-accepted access.
    }
};
