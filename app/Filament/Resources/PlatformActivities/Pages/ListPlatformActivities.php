<?php

namespace App\Filament\Resources\PlatformActivities\Pages;

use App\Filament\Resources\PlatformActivities\PlatformActivityResource;
use App\Filament\Widgets\PlatformActivityOverview;
use Filament\Resources\Pages\ListRecords;

class ListPlatformActivities extends ListRecords
{
    protected static string $resource = PlatformActivityResource::class;

    public function getSubheading(): ?string
    {
        return 'Friendly operational activity for support and admin workflows. Use Audit log for owner-only raw accountability records.';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PlatformActivityOverview::class,
        ];
    }
}
