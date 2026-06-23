<?php

namespace App\Support;

class PlatformAccess
{
    public static function usesWorkspaceInterface(): bool
    {
        return ! (auth()->user()?->isPlatformAdmin() ?? false);
    }
}
