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
            Stat::make('Payments needing attention', number_format(
                Project::query()
                    ->whereIn('invoice_status', [Project::INVOICE_PENDING, Project::INVOICE_SENT, Project::INVOICE_OVERDUE])
                    ->count()
            ))
                ->description('Approved projects awaiting invoice/payment')
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
