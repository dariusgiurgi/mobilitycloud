<?php

namespace App\Support;

use Filament\Facades\Filament;

trait AuthorizesWorkspaceManagement
{
    protected function authorizeWorkspaceManagement(): void
    {
        $workspace = Filament::getTenant();

        abort_unless(PlatformAccess::usesWorkspaceInterface() && $workspace && $workspace->canBeManagedBy(auth()->user()), 403);
    }
}
