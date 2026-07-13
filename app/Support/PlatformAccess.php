<?php

namespace App\Support;

class PlatformAccess
{
    public static function usesWorkspaceInterface(): bool
    {
        return ! (auth()->user()?->isPlatformAdmin() ?? false)
            && AccountAccess::isSubscriptionActive(auth()->user());
    }

    public static function canUse(string $module): bool
    {
        if (! self::usesWorkspaceInterface() || ! AccountAccess::moduleEnabled(auth()->user(), $module)) {
            return false;
        }

        return true;
    }

    public static function canPreview(string $module): bool
    {
        return self::usesWorkspaceInterface();
    }

    public static function isReadOnly(): bool
    {
        return AccountAccess::isReadOnly(auth()->user());
    }
}
