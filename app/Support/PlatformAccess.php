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
        $user = auth()->user();

        if (! self::usesWorkspaceInterface() || ! WorkspaceAccess::moduleEnabled($workspace, $module)) {
            return false;
        }

        if ($workspace?->isProjectOnlyFor($user)) {
            return in_array($module, [
                PlanCatalog::MODULE_PROJECTS,
                PlanCatalog::MODULE_TASKS,
            ], true);
        }

        return true;
    }

    public static function isReadOnly(): bool
    {
        return WorkspaceAccess::isReadOnly(Filament::getTenant());
    }
}
