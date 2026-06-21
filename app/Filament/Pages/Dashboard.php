<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Widgets\DashboardWorkspace;
use App\Filament\Widgets\ProjectStatsOverview;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Workspace overview';

    public function getSubheading(): ?string
    {
        $workspace = Filament::getTenant();

        return $workspace
            ? $workspace->name.' · Your projects, priorities and upcoming milestones.'
            : 'Your projects, priorities and upcoming milestones.';
    }

    protected function getHeaderActions(): array
    {
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
        return [
            ProjectStatsOverview::class,
            DashboardWorkspace::class,
        ];
    }
}
