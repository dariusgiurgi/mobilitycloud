<?php

namespace App\Filament\Resources\PlatformAnnouncements\Pages;

use App\Filament\Resources\PlatformAnnouncements\PlatformAnnouncementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPlatformAnnouncements extends ListRecords
{
    protected static string $resource = PlatformAnnouncementResource::class;

    public function getSubheading(): ?string
    {
        return 'Create header notices for maintenance, outages, urgent support notes or product announcements.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New announcement'),
        ];
    }
}
