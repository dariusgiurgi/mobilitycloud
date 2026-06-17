<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProjectStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $workspace = Filament::getTenant();

        $base = Project::query()->where('workspace_id', $workspace?->id);

        $draft     = (clone $base)->where('status', 'draft')->count();
        $active    = (clone $base)->whereIn('status', ['approved', 'activated'])->count();
        $completed = (clone $base)->where('status', 'completed')->count();

        $totalBudget = (clone $base)->sum('total_budget');

        return [
            Stat::make('Draft', $draft)
                ->description('Projects in writing')
                ->color('gray'),

            Stat::make('Active', $active)
                ->description('Approved or activated')
                ->color('success'),

            Stat::make('Completed', $completed)
                ->description('Finished projects')
                ->color('info'),

            Stat::make('Total budget', '€ ' . number_format((float) $totalBudget, 2))
                ->description('Across all projects')
                ->color('primary'),
        ];
    }
}
