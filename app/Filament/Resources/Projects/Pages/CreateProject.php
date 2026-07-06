<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use App\Models\Workspace;
use App\Services\AccountWorkspaceService;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\RedirectResponse;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    public function mount(): void
    {
        $workspace = $this->accountWorkspace();
        $tenant = Filament::getTenant();

        if ($tenant instanceof Workspace && (int) $tenant->getKey() !== (int) $workspace->getKey()) {
            throw new HttpResponseException(new RedirectResponse(ProjectResource::getUrl('create', tenant: $workspace)));
        }

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
        $data['workspace_id'] = $this->accountWorkspace()->getKey();
        $data['access_mode'] = 'restricted';

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $workspace = $this->accountWorkspace();
        $data['workspace_id'] = $workspace->getKey();

        $record = new Project($data);
        $record->workspace()->associate($workspace);
        $record->save();

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return ProjectResource::getUrl('overview', ['record' => $this->record], tenant: $this->record->workspace);
    }

    private function accountWorkspace(): Workspace
    {
        return app(AccountWorkspaceService::class)->ensureFor(auth()->user());
    }
}
