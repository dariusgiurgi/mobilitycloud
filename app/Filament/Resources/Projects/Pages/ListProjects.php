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
            ->withCount('participants')
            ->with('budgetLines.expenses')
            ->orderByRaw("CASE status
                WHEN 'active' THEN 0
                WHEN 'approved' THEN 1
                WHEN 'revise' THEN 2
                WHEN 'writing' THEN 3
                WHEN 'submitted' THEN 4
                WHEN 'completed' THEN 5
                WHEN 'rejected' THEN 6
                ELSE 7 END")
            ->latest('updated_at')
            ->get();
    }

    public function getProjectUrl(Project $project): string
    {
        return ProjectResource::getUrl('overview', ['record' => $project]);
    }
}
