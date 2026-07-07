<?php

namespace App\Filament\Widgets;

use App\Models\User;
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
                $this->accountQuery()->where('subscription_status', 'active')->count()
            ))
                ->description('Accounts with normal access')
                ->color('success'),
            Stat::make('Trials', number_format(
                $this->accountQuery()->where('subscription_status', 'trial')->count()
            ))
                ->description('Evaluation accounts')
                ->color('info'),
            Stat::make('Demo accounts', number_format(
                $this->accountQuery()
                    ->where(fn (Builder $query): Builder => $query
                        ->where('plan', 'demo')
                        ->orWhere('subscription_status', 'demo'))
                    ->count()
            ))
                ->description('Internal/test environments')
                ->color('warning'),
            Stat::make('Needs attention', number_format(
                $this->accountQuery()
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

    private function accountQuery(): Builder
    {
        return User::query()->where('role', User::ROLE_USER);
    }
}
