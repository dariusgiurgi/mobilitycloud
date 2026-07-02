<?php

namespace App\Console\Commands;

use App\Models\Workspace;
use App\Services\DemoWorkspaceResetService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class ResetDemoWorkspaces extends Command
{
    protected $signature = 'demo:reset-workspaces {--force : Reset all demo workspaces regardless of schedule}';

    protected $description = 'Reset volatile sandbox data for scheduled demo workspaces';

    public function handle(DemoWorkspaceResetService $resetService): int
    {
        $workspaces = Workspace::query()
            ->where(fn (Builder $query): Builder => $query
                ->where('plan', 'demo')
                ->orWhere('subscription_status', 'demo'))
            ->where(function (Builder $query): void {
                if ($this->option('force')) {
                    return;
                }

                $query
                    ->where(function (Builder $query): void {
                        $query
                            ->where('demo_reset_frequency', 'daily')
                            ->where(fn (Builder $query): Builder => $query
                                ->whereNull('demo_last_reset_at')
                                ->orWhere('demo_last_reset_at', '<=', now()->subDay()));
                    })
                    ->orWhere(function (Builder $query): void {
                        $query
                            ->where('demo_reset_frequency', 'weekly')
                            ->where(fn (Builder $query): Builder => $query
                                ->whereNull('demo_last_reset_at')
                                ->orWhere('demo_last_reset_at', '<=', now()->subWeek()));
                    });
            })
            ->get();

        foreach ($workspaces as $workspace) {
            $counts = $resetService->reset($workspace);

            $this->line(sprintf(
                'Reset %s: %d projects, %d blocks, %d calculations, %d invitations.',
                $workspace->name,
                $counts['projects'],
                $counts['content_blocks'],
                $counts['saved_calculations'],
                $counts['invitations'],
            ));
        }

        $this->info($workspaces->count().' demo '.str('workspace')->plural($workspaces->count()).' reset.');

        return self::SUCCESS;
    }
}
