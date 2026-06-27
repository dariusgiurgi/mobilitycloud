<?php

namespace App\Filament\Resources\PlatformWorkspaces\Pages;

use App\Filament\Resources\PlatformWorkspaces\PlatformWorkspaceResource;
use App\Support\PlatformAudit;
use Filament\Resources\Pages\EditRecord;

class EditPlatformWorkspace extends EditRecord
{
    protected static string $resource = PlatformWorkspaceResource::class;

    public function getSubheading(): ?string
    {
        return $this->record->users()->count().' user(s) · '.$this->record->projects()->count().' project(s)';
    }

    protected function afterSave(): void
    {
        PlatformAudit::log('workspace.updated', 'Updated workspace '.$this->record->name, $this->record);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
