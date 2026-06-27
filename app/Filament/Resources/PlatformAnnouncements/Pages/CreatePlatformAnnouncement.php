<?php

namespace App\Filament\Resources\PlatformAnnouncements\Pages;

use App\Filament\Resources\PlatformAnnouncements\PlatformAnnouncementResource;
use App\Support\PlatformAudit;
use Filament\Resources\Pages\CreateRecord;

class CreatePlatformAnnouncement extends CreateRecord
{
    protected static string $resource = PlatformAnnouncementResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        PlatformAudit::log('announcement.created', 'Created announcement '.$this->record->title, $this->record);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
