<?php

namespace App\Filament\Resources\PlatformWorkspaces\Pages;

use App\Filament\Resources\PlatformWorkspaces\PlatformWorkspaceResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPlatformWorkspace extends ViewRecord
{
    protected static string $resource = PlatformWorkspaceResource::class;

    public function getTitle(): string
    {
        return $this->record->name;
    }

    public function getSubheading(): ?string
    {
        return $this->record->subscriptionStatusLabel().' · '.str($this->record->plan ?: 'free')->replace('_', ' ')->title().' · '.$this->record->users()->count().' user(s)';
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
