<?php

namespace App\Filament\Widgets;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProjectStatsOverview extends BaseWidget
{
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $workspaceId = Filament::getTenant()?->id;

        $projects = Project::query()
            ->where('workspace_id', $workspaceId)
            ->accessibleTo(auth()->user(), Filament::getTenant())
            ->whereIn('status', [ProjectStatus::Approved->value, ProjectStatus::Active->value])
            ->with('budgetLines.expenses')
            ->get();

        $approvedFunding = (float) $projects->sum->effective_budget;
        $spent = (float) $projects->sum->spent;
        $available = $approvedFunding - $spent;

        return [
            Stat::make('Active projects', $projects->count())
                ->description('Approved or in implementation')
                ->color('success'),

            Stat::make('Approved funding', '€ '.number_format($approvedFunding, 2))
                ->description('Across current projects')
                ->color('primary'),

            Stat::make('Total spent', '€ '.number_format($spent, 2))
                ->description($approvedFunding > 0 ? round($spent / $approvedFunding * 100).'% of available funding' : 'No approved funding yet')
                ->color('info'),

            Stat::make('Available balance', '€ '.number_format($available, 2))
                ->description($available < 0 ? 'Budget exceeded' : 'Remaining to allocate and spend')
                ->color($available < 0 ? 'danger' : 'gray'),
        ];
    }
}
