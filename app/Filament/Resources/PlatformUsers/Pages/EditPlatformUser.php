<?php

namespace App\Filament\Resources\PlatformUsers\Pages;

use App\Filament\Resources\PlatformUsers\PlatformUserResource;
use App\Support\PlatformAudit;
use Filament\Resources\Pages\EditRecord;

class EditPlatformUser extends EditRecord
{
    protected static string $resource = PlatformUserResource::class;

    public function getSubheading(): ?string
    {
        return $this->record->email.' · '.$this->record->workspaces()->count().' workspace(s)';
    }

    protected function afterSave(): void
    {
        PlatformAudit::log('account.updated', 'Updated account '.$this->record->email, $this->record);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
