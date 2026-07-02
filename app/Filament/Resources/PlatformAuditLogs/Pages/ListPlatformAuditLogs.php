<?php

namespace App\Filament\Resources\PlatformAuditLogs\Pages;

use App\Filament\Resources\PlatformAuditLogs\PlatformAuditLogResource;
use Filament\Resources\Pages\ListRecords;

class ListPlatformAuditLogs extends ListRecords
{
    protected static string $resource = PlatformAuditLogResource::class;

    public function getSubheading(): ?string
    {
        return 'Owner-only raw audit trail for legal/accountability review. Day-to-day support should use Activity center.';
    }
}
