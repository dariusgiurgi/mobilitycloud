<?php

namespace App\Filament\Resources\PlatformAnnouncements\Pages;

use App\Filament\Resources\PlatformAnnouncements\PlatformAnnouncementResource;
use App\Services\PlatformCommunicationDeliveryService;
use App\Support\PlatformAudit;
use Filament\Notifications\Notification;
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
        PlatformAudit::log('communication.created', 'Created communication '.$this->record->title, $this->record);

        if ($this->record->sendsNotification()) {
            $sent = app(PlatformCommunicationDeliveryService::class)->sendNotification($this->record);

            Notification::make()
                ->title('Notification delivered')
                ->body($sent.' '.str('recipient')->plural($sent).' reached.')
                ->success()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
