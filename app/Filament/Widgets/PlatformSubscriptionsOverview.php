<?php

namespace App\Filament\Widgets;

use App\Models\Workspace;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class PlatformSubscriptionsOverview extends BaseWidget
{
    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        return [
            Stat::make('Active subscriptions', number_format(
                Workspace::query()->where('subscription_status', 'active')->count()
            ))
                ->description('Workspaces with normal access')
                ->color('success'),
            Stat::make('Trials', number_format(
                Workspace::query()->where('subscription_status', 'trial')->count()
            ))
                ->description('Evaluation workspaces')
                ->color('info'),
            Stat::make('Demo workspaces', number_format(
                Workspace::query()
                    ->where(fn (Builder $query): Builder => $query
                        ->where('plan', 'demo')
                        ->orWhere('subscription_status', 'demo'))
                    ->count()
            ))
                ->description('Internal/test environments')
                ->color('warning'),
            Stat::make('Needs attention', number_format(
                Workspace::query()
                    ->where(function (Builder $query): void {
                        $query
                            ->where('is_suspended', true)
                            ->orWhereIn('subscription_status', ['expired', 'suspended'])
                            ->orWhere(function (Builder $query): void {
                                $query
                                    ->whereNotNull('subscription_ends_at')
                                    ->where('subscription_ends_at', '<=', now()->addDays(14));
                            })
                            ->orWhere(function (Builder $query): void {
                                $query
                                    ->where('subscription_status', 'trial')
                                    ->whereNotNull('trial_ends_at')
                                    ->where('trial_ends_at', '<=', now()->addDays(7));
                            });
                    })
                    ->count()
            ))
                ->description('Expired, suspended or ending soon')
                ->color('danger'),
        ];
    }
}
