<?php

namespace App\Filament\Resources\PlatformAnnouncements\Pages;

use App\Filament\Resources\PlatformAnnouncements\PlatformAnnouncementResource;
use App\Support\PlatformAudit;
use Filament\Resources\Pages\EditRecord;

class EditPlatformAnnouncement extends EditRecord
{
    protected static string $resource = PlatformAnnouncementResource::class;

    protected function afterSave(): void
    {
        PlatformAudit::log('announcement.updated', 'Updated announcement '.$this->record->title, $this->record);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
