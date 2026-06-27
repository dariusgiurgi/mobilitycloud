<?php

namespace App\Filament\Widgets;

use App\Models\PlatformAnnouncement;
use App\Models\Project;
use App\Models\PublicBlockReport;
use App\Models\User;
use App\Models\Workspace;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStatsOverview extends BaseWidget
{
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $users = User::query();
        $workspaces = Workspace::query();

        return [
            Stat::make('Users', number_format((clone $users)->count()))
                ->description((clone $users)->where('created_at', '>=', now()->subDays(30))->count().' new in 30 days')
                ->color('primary'),
            Stat::make('Workspaces', number_format((clone $workspaces)->count()))
                ->description((clone $workspaces)->where('is_suspended', true)->count().' suspended')
                ->color('info'),
            Stat::make('Projects', number_format(Project::query()->count()))
                ->description('Across every workspace')
                ->color('success'),
            Stat::make('Subscriptions needing attention', number_format(
                Workspace::query()
                    ->where(fn ($query) => $query
                        ->whereIn('subscription_status', ['expired', 'suspended'])
                        ->orWhere(fn ($query) => $query
                            ->whereNotNull('subscription_ends_at')
                            ->where('subscription_ends_at', '<=', now()->addDays(14))))
                    ->count()
            ))
                ->description('Expired, suspended or ending soon')
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
