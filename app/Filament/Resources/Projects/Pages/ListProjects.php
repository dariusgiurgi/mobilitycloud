<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.pages.list-projects-cards';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New project'),
        ];
    }

    public function getProjects()
    {
        return Project::query()
            ->where('workspace_id', Filament::getTenant()?->id)
            ->withCount('budgetLines')
            ->with('budgetLines.expenses')
            ->latest()
            ->get();
    }

    public function getProjectUrl(Project $project): string
    {
        // Click pe card → hub-ul proiectului (Overview cu modulele).
        return ProjectResource::getUrl('overview', ['record' => $project]);
    }

    public function getSettingsUrl(Project $project): string
    {
        // Rotita → setari (Edit)
        return ProjectResource::getUrl('edit', ['record' => $project]);
    }
}
