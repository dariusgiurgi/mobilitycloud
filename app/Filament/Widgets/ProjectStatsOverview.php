<?php

namespace App\Filament\Widgets;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProjectStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $workspaceId = Filament::getTenant()?->id;

        $base = Project::query()->where('workspace_id', $workspaceId);

        // Lifecycle pipeline (values come from the enum, so they never drift).
        $writing = (clone $base)
            ->whereIn('status', [ProjectStatus::Writing->value, ProjectStatus::Revise->value])
            ->count();

        $submitted = (clone $base)
            ->where('status', ProjectStatus::Submitted->value)
            ->count();

        $active = (clone $base)
            ->whereIn('status', [ProjectStatus::Approved->value, ProjectStatus::Active->value])
            ->count();

        $completed = (clone $base)
            ->where('status', ProjectStatus::Completed->value)
            ->count();

        // Effective grant: approved amount once confirmed, otherwise requested.
        $totalGrant = (float) (clone $base)
            ->selectRaw('COALESCE(SUM(CASE WHEN approved_budget > 0 THEN approved_budget ELSE total_budget END), 0) as t')
            ->value('t');

        return [
            Stat::make('Being written', $writing)
                ->description('Writing or revising')
                ->color('gray'),

            Stat::make('Awaiting result', $submitted)
                ->description('Submitted to the funder')
                ->color('info'),

            Stat::make('In implementation', $active)
                ->description('Approved or active')
                ->color('success'),

            Stat::make('Total grant', '€ ' . number_format($totalGrant, 2))
                ->description($completed . ' completed · across all projects')
                ->color('primary'),
        ];
    }
}