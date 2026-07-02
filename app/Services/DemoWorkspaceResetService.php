<?php

namespace App\Services;

use App\Models\ContentBlock;
use App\Models\Project;
use App\Models\SavedCalculation;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Support\PlatformSubscriptionTimeline;
use Illuminate\Support\Facades\DB;

class DemoWorkspaceResetService
{
    /**
     * Reset only volatile sandbox data. The workspace, members, billing,
     * branding, subscription settings and platform timeline are preserved.
     *
     * @return array{projects: int, content_blocks: int, saved_calculations: int, invitations: int}
     */
    public function reset(Workspace $workspace): array
    {
        return DB::transaction(function () use ($workspace): array {
            $counts = [
                'projects' => Project::query()
                    ->where('workspace_id', $workspace->id)
                    ->withTrashed()
                    ->count(),
                'content_blocks' => ContentBlock::query()
                    ->where('workspace_id', $workspace->id)
                    ->count(),
                'saved_calculations' => SavedCalculation::query()
                    ->where('workspace_id', $workspace->id)
                    ->count(),
                'invitations' => WorkspaceInvitation::query()
                    ->where('workspace_id', $workspace->id)
                    ->count(),
            ];

            Project::query()
                ->where('workspace_id', $workspace->id)
                ->withTrashed()
                ->get()
                ->each
                ->forceDelete();

            ContentBlock::query()
                ->where('workspace_id', $workspace->id)
                ->delete();

            SavedCalculation::query()
                ->where('workspace_id', $workspace->id)
                ->delete();

            WorkspaceInvitation::query()
                ->where('workspace_id', $workspace->id)
                ->delete();

            $workspace->forceFill(['demo_last_reset_at' => now()])->save();

            PlatformSubscriptionTimeline::record($workspace, 'demo_reset', 'Demo workspace sandbox data reset.', [
                'counts' => $counts,
            ]);

            return $counts;
        });
    }
}
