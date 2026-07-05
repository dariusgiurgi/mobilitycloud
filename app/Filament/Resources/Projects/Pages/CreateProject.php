<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    public function getTitle(): string
    {
        return 'Create a new project';
    }

    public function getSubheading(): ?string
    {
        return 'Start with the project identity and planning data. Application, budget, participants and documents will remain connected to this record.';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['workspace_id'] = Filament::getTenant()->getKey();
        $data['access_mode'] = 'restricted';

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return ProjectResource::getUrl('overview', ['record' => $this->record]);
    }
}
