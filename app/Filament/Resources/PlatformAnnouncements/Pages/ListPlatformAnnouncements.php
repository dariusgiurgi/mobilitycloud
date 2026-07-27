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
        return 'Create platform banners or send in-app notifications to all users, selected groups or individual accounts.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New communication'),
        ];
    }
}
