<?php

namespace App\Filament\Widgets;

use App\Models\PlatformAnnouncement;
use App\Models\Project;
use App\Models\PublicBlockReport;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStatsOverview extends BaseWidget
{
    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $users = User::query();

        return [
            Stat::make('Users', number_format((clone $users)->count()))
                ->description((clone $users)->where('created_at', '>=', now()->subDays(30))->count().' new in 30 days')
                ->color('primary'),
            Stat::make('Suspended accounts', number_format((clone $users)->where('is_suspended', true)->count()))
                ->description('Blocked from client modules')
                ->color('info'),
            Stat::make('Projects', number_format(Project::query()->count()))
                ->description('Owned projects across all accounts')
                ->color('success'),
            Stat::make('Subscriptions needing attention', number_format(
                User::query()
                    ->where(fn ($query) => $query
                        ->whereIn('subscription_status', ['expired', 'suspended'])
                        ->orWhere(fn ($query) => $query
                            ->whereNotNull('subscription_ends_at')
                            ->where('subscription_ends_at', '<=', now()->addDays(14)))
                        ->orWhere(fn ($query) => $query
                            ->where('subscription_status', 'trial')
                            ->whereNotNull('trial_ends_at')
                            ->where('trial_ends_at', '<=', now()->addDays(7))))
                    ->count()
            ))
                ->description('Expired, suspended, trial or paid access ending soon')
                ->color('warning'),
            Stat::make('Moderation reports', number_format(
                PublicBlockReport::query()->where('status', PublicBlockReport::STATUS_PENDING)->count()
            ))
                ->description('Pending review')
                ->color('danger'),
            Stat::make('Active announcements', number_format(PlatformAnnouncement::query()->active()->count()))
                ->description('Visible banners')
                ->color('gray'),
        ];
    }
}
