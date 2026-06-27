<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\PlatformAnnouncements\PlatformAnnouncementResource;
use App\Filament\Resources\PlatformUsers\PlatformUserResource;
use App\Filament\Resources\PlatformWorkspaces\PlatformWorkspaceResource;
use App\Filament\Resources\PublicBlockReports\PublicBlockReportResource;
use App\Models\PlatformAuditLog;
use App\Models\User;
use App\Models\Workspace;
use Filament\Widgets\Widget;

class PlatformOperationsOverview extends Widget
{
    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.platform-operations-overview';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        return [
            'recentUsers' => User::query()->latest()->limit(5)->get(),
            'endingSoon' => Workspace::query()
                ->whereNotNull('subscription_ends_at')
                ->where('subscription_ends_at', '<=', now()->addDays(30))
                ->orderBy('subscription_ends_at')
                ->limit(5)
                ->get(),
            'recentAudit' => PlatformAuditLog::query()->with('actor')->latest()->limit(6)->get(),
            'links' => [
                ['label' => 'Accounts', 'url' => PlatformUserResource::getUrl(), 'detail' => 'Manage users and reset passwords'],
                ['label' => 'Workspaces', 'url' => PlatformWorkspaceResource::getUrl(), 'detail' => 'Plans, trials and subscription dates'],
                ['label' => 'Announcements', 'url' => PlatformAnnouncementResource::getUrl(), 'detail' => 'Header notices and incidents'],
                ['label' => 'Moderation reports', 'url' => PublicBlockReportResource::getUrl(), 'detail' => 'Review public content reports'],
            ],
        ];
    }
}
