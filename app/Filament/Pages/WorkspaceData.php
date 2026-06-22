<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class WorkspaceData extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBoxArrowDown;

    protected static ?string $navigationLabel = 'Data & backup';

    protected static string|\UnitEnum|null $navigationGroup = 'Workspace settings';

    protected static ?int $navigationSort = 30;

    protected static ?string $title = 'Workspace data & backup';

    protected string $view = 'filament.pages.workspace-data';

    public static function canAccess(): bool
    {
        return Filament::getTenant()?->canManageMembersBy(auth()->user()) ?? false;
    }

    public function getSubheading(): ?string
    {
        return 'Keep an independent copy of your organisation’s records and uploaded files.';
    }

    public function getBackupUrlProperty(): string
    {
        return route('workspaces.backup', Filament::getTenant());
    }
}
