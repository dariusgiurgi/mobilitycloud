<?php

namespace App\Filament\Pages;

use App\Filament\Resources\PlatformUsers\PlatformUserResource;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\PublicBlockReports\PublicBlockReportResource;
use App\Filament\Widgets\DashboardOverview;
use App\Filament\Widgets\PlatformOperationsOverview;
use App\Filament\Widgets\PlatformStatsOverview;
use App\Filament\Widgets\ProjectStatsOverview;
use App\Models\Project;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Project dashboard';

    public static function getNavigationLabel(): string
    {
        return auth()->user()?->isPlatformAdmin()
            ? 'Platform overview'
            : 'Project dashboard';
    }

    public function getSubheading(): ?string
    {
        if (auth()->user()?->isPlatformAdmin()) {
            return 'Your daily overview of accounts, project payments and support priorities.';
        }

        return 'Your projects, priorities and upcoming milestones.';
    }

    protected function getHeaderActions(): array
    {
        if (auth()->user()?->isPlatformAdmin()) {
            return [
                Action::make('accounts')
                    ->label('Manage accounts')
                    ->icon(Heroicon::OutlinedUsers)
                    ->url(fn (): string => PlatformUserResource::getUrl()),
                Action::make('moderationReports')
                    ->color('gray')
                    ->label('Review moderation reports')
                    ->icon(Heroicon::OutlinedFlag)
                    ->url(fn (): string => PublicBlockReportResource::getUrl()),
            ];
        }

        return [
            Action::make('newProject')
                ->label('New project')
                ->icon(Heroicon::OutlinedPlus)
                ->url(fn (): string => ProjectResource::accountUrl('create'))
                ->visible(fn (): bool => auth()->user()?->can('create', Project::class) ?? false),
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
            DashboardOverview::class,
        ];
    }

    public function getTitle(): string
    {
        return auth()->user()?->isPlatformAdmin()
            ? 'Platform administration'
            : parent::getTitle();
    }
}
