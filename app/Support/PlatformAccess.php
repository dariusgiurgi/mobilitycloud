<?php

namespace App\Support;

use Filament\Facades\Filament;

class PlatformAccess
{
    public static function usesWorkspaceInterface(): bool
    {
        return ! (auth()->user()?->isPlatformAdmin() ?? false)
            && WorkspaceAccess::isSubscriptionActive(Filament::getTenant());
    }

    public static function canUse(string $module): bool
    {
        $workspace = Filament::getTenant();
        if (! self::usesWorkspaceInterface() || ! WorkspaceAccess::moduleEnabled($workspace, $module)) {
            return false;
        }

        return true;
    }

    public static function isReadOnly(): bool
    {
        return WorkspaceAccess::isReadOnly(Filament::getTenant());
    }
}
