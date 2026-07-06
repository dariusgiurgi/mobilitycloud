<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    public function mount(): void
    {
        parent::mount();
    }

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
        $data['owner_id'] = auth()->id();
        $data['workspace_id'] = null;
        $data['access_mode'] = 'restricted';

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $data['owner_id'] = auth()->id();
        $data['workspace_id'] = null;

        $record = new Project($data);
        $record->save();

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return ProjectResource::projectUrl($this->record);
    }

}
