<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\PublicBlockReports\PublicBlockReportResource;
use App\Filament\Widgets\DashboardWorkspace;
use App\Filament\Widgets\PlatformOperationsOverview;
use App\Filament\Widgets\PlatformStatsOverview;
use App\Filament\Widgets\ProjectStatsOverview;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Workspace overview';

    public static function getNavigationLabel(): string
    {
        return auth()->user()?->isPlatformAdmin()
            ? 'Platform overview'
            : 'Workspace overview';
    }

    public function getSubheading(): ?string
    {
        if (auth()->user()?->isPlatformAdmin()) {
            return 'Internal platform administration for users, reports and operational controls.';
        }

        $workspace = Filament::getTenant();

        return $workspace
            ? $workspace->name.' · Your projects, priorities and upcoming milestones.'
            : 'Your projects, priorities and upcoming milestones.';
    }

    protected function getHeaderActions(): array
    {
        if (auth()->user()?->isPlatformAdmin()) {
            return [
                Action::make('moderationReports')
                    ->label('Review moderation reports')
                    ->icon(Heroicon::OutlinedFlag)
                    ->url(fn (): string => PublicBlockReportResource::getUrl()),
            ];
        }

        return [
            Action::make('newProject')
                ->label('New project')
                ->icon(Heroicon::OutlinedPlus)
                ->url(fn (): string => ProjectResource::getUrl('create'))
                ->visible(fn (): bool => Filament::getTenant()?->canBeManagedBy(auth()->user()) ?? false),
        ];
    }

    public function getWidgets(): array
    {
        if (auth()->user()?->isPlatformAdmin()) {
            return [
                PlatformStatsOverview::class,
                PlatformOperationsOverview::class,
            ];
        }

        return [
            ProjectStatsOverview::class,
            DashboardWorkspace::class,
        ];
    }

    public function getTitle(): string
    {
        return auth()->user()?->isPlatformAdmin()
            ? 'Platform administration'
            : parent::getTitle();
    }
}
