<?php

namespace App\Filament\Widgets;

use App\Models\PlatformAuditLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformActivityOverview extends BaseWidget
{
    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        return [
            Stat::make('Actions today', number_format(
                PlatformAuditLog::query()->where('created_at', '>=', today())->count()
            ))
                ->description('All audited platform actions')
                ->color('primary'),
            Stat::make('Impersonations · 7 days', number_format(
                PlatformAuditLog::query()
                    ->where('action', 'impersonation.started')
                    ->where('created_at', '>=', now()->subDays(7))
                    ->count()
            ))
                ->description('Support access sessions')
                ->color('warning'),
            Stat::make('Account changes · 7 days', number_format(
                PlatformAuditLog::query()
                    ->where('action', 'like', 'account.%')
                    ->where('created_at', '>=', now()->subDays(7))
                    ->count()
            ))
                ->description('Created, edited, suspended or deleted')
                ->color('info'),
            Stat::make('Subscription changes · 7 days', number_format(
                PlatformAuditLog::query()
                    ->where('action', 'like', 'workspace.%')
                    ->where('created_at', '>=', now()->subDays(7))
                    ->count()
            ))
                ->description('Plans, trials, billing, demo or access')
                ->color('success'),
        ];
    }
}
