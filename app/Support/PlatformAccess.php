<?php

namespace App\Support;

class PlatformAccess
{
    public static function usesProductInterface(): bool
    {
        return ! (auth()->user()?->isPlatformAdmin() ?? false)
            && AccountAccess::isSubscriptionActive(auth()->user());
    }

    public static function canUse(string $module): bool
    {
        if (! self::usesProductInterface() || ! AccountAccess::moduleEnabled(auth()->user(), $module)) {
            return false;
        }

        return true;
    }

    public static function canPreview(string $module): bool
    {
        return self::usesProductInterface();
    }

    public static function isReadOnly(): bool
    {
        return AccountAccess::isReadOnly(auth()->user());
    }
}
