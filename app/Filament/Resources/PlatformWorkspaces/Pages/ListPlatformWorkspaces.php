<?php

namespace App\Filament\Resources\PlatformWorkspaces\Pages;

use App\Filament\Resources\PlatformWorkspaces\PlatformWorkspaceResource;
use Filament\Resources\Pages\ListRecords;

class ListPlatformWorkspaces extends ListRecords
{
    protected static string $resource = PlatformWorkspaceResource::class;

    public function getSubheading(): ?string
    {
        return 'Organisations, plans, expiration dates, owner accounts and operational status.';
    }
}
