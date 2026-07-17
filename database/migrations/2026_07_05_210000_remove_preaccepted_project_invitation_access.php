<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_invitations')
            ->select('project_invitations.project_id', 'users.id as user_id')
            ->join('users', function ($join): void {
                $join->whereRaw('LOWER(users.email) = LOWER(project_invitations.email)');
            })
            ->whereNotNull('project_invitations.project_id')
            ->whereNull('project_invitations.accepted_at')
            ->where('project_invitations.role', 'like', 'project_%')
            ->orderBy('project_invitations.id')
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
