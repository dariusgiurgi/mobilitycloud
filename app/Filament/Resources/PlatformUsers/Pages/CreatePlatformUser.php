<?php

namespace App\Filament\Resources\PlatformUsers\Pages;

use App\Filament\Resources\PlatformUsers\PlatformUserResource;
use App\Support\PlatformAudit;
use Filament\Resources\Pages\CreateRecord;

class CreatePlatformUser extends CreateRecord
{
    protected static string $resource = PlatformUserResource::class;

    protected function afterCreate(): void
    {
        PlatformAudit::log('account.created', 'Created account '.$this->record->email, $this->record);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
