<?php

namespace App\Filament\Pages;

use App\Services\WorkspaceRestoreService;
use App\Support\PlanCatalog;
use App\Support\PlatformAccess;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class WorkspaceData extends Page
{
    use WithFileUploads;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBoxArrowDown;

    protected static ?string $navigationLabel = 'Backup & exports';

    protected static string|\UnitEnum|null $navigationGroup = 'Workspace settings';

    protected static ?int $navigationSort = 30;

    protected static ?string $title = 'Backup & exports';

    protected string $view = 'filament.pages.workspace-data';

    public $restoreFile = null;

    public ?array $restoreResult = null;

    public static function canAccess(): bool
    {
        return PlatformAccess::canUse(PlanCatalog::MODULE_SETTINGS)
            && (Filament::getTenant()?->canManageMembersBy(auth()->user()) ?? false);
    }

    public function getSubheading(): ?string
    {
        return 'Download, restore and export workspace records and uploaded files.';
    }

    public function getBackupUrlProperty(): string
    {
        return route('workspaces.backup', Filament::getTenant());
    }

    public function restore(WorkspaceRestoreService $restores): void
    {
        $workspace = Filament::getTenant();
        abort_unless($workspace?->canManageMembersBy(auth()->user()), 403);
        $this->validate([
            'restoreFile' => ['required', 'file', 'mimes:zip', 'max:102400'],
        ]);

        $this->restoreResult = $restores->restore($workspace, $this->restoreFile->getRealPath());
        $this->restoreFile = null;

        Notification::make()
            ->title('Workspace backup restored')
            ->body($this->restoreResult['projects'].' project(s) and '.$this->restoreResult['files'].' file(s) imported.')
            ->success()
            ->send();
    }
}
