<?php

namespace App\Filament\Widgets;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProjectStatsOverview extends BaseWidget
{
    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.project-stats-overview';

    protected ?string $pollingInterval = null;

    protected int|array|null $columns = [
        'default' => 1,
        'md' => 2,
        'xl' => 4,
    ];

    protected function getStats(): array
    {
        $projects = Project::query()
            ->visibleToAccount(auth()->user())
            ->whereIn('status', [ProjectStatus::Approved->value, ProjectStatus::Active->value])
            ->with('budgetLines.expenses')
            ->get();

        $approvedFunding = (float) $projects->sum->effective_budget;
        $spent = (float) $projects->sum->spent;
        $available = $approvedFunding - $spent;

        return [
            Stat::make('Approved projects', $projects->count())
                ->description('Ready for management')
                ->color('success')
                ->extraAttributes(['class' => 'mc-project-stats']),

            Stat::make('Approved funding', $this->formatCurrencyStat($approvedFunding))
                ->description('Across current projects')
                ->color('primary')
                ->extraAttributes(['class' => 'mc-project-stats mc-project-stats-money']),

            Stat::make('Total spent', $this->formatCurrencyStat($spent))
                ->description($approvedFunding > 0 ? round($spent / $approvedFunding * 100).'% of available funding' : 'No approved funding yet')
                ->color('info')
                ->extraAttributes(['class' => 'mc-project-stats mc-project-stats-money']),

            Stat::make('Available balance', $this->formatCurrencyStat($available))
                ->description($available < 0 ? 'Budget exceeded' : 'Remaining to allocate and spend')
                ->color($available < 0 ? 'danger' : 'gray')
                ->extraAttributes(['class' => 'mc-project-stats mc-project-stats-money']),
        ];
    }

    private function formatCurrencyStat(float $amount): string
    {
        return "€\u{00A0}".number_format($amount, 0);
    }
}
