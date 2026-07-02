<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class PlatformPermissions extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Audit & operations';

    protected static ?string $navigationLabel = 'Permissions matrix';

    protected static ?int $navigationSort = 30;

    protected static ?string $title = 'Permissions matrix';

    protected string $view = 'filament.pages.platform-permissions';

    public static function canAccess(): bool
    {
        return auth()->user()?->isPlatformAdmin() ?? false;
    }

    public function getSubheading(): ?string
    {
        return 'Clear operational boundaries for platform owner and platform admin accounts.';
    }

    public function permissions(): array
    {
        return [
            ['area' => 'Accounts', 'permission' => 'View customer accounts', 'owner' => true, 'admin' => true],
            ['area' => 'Accounts', 'permission' => 'Create platform staff accounts', 'owner' => true, 'admin' => false],
            ['area' => 'Accounts', 'permission' => 'Edit platform owner/admin accounts', 'owner' => true, 'admin' => false],
            ['area' => 'Accounts', 'permission' => 'Reset customer passwords', 'owner' => true, 'admin' => true],
            ['area' => 'Accounts', 'permission' => 'Archive / restore accounts', 'owner' => true, 'admin' => false],
            ['area' => 'Accounts', 'permission' => 'Permanently delete accounts', 'owner' => true, 'admin' => false],
            ['area' => 'Support', 'permission' => 'Impersonate customer accounts with audit reason', 'owner' => true, 'admin' => true],
            ['area' => 'Support', 'permission' => 'Add account and workspace notes', 'owner' => true, 'admin' => true],
            ['area' => 'Billing & access', 'permission' => 'View subscriptions and billing readiness', 'owner' => true, 'admin' => true],
            ['area' => 'Billing & access', 'permission' => 'Activate, suspend or expire workspaces', 'owner' => true, 'admin' => true],
            ['area' => 'Billing & access', 'permission' => 'Grant manual access outside subscription rules', 'owner' => true, 'admin' => false],
            ['area' => 'Billing & access', 'permission' => 'Create demo workspaces and reset demo data', 'owner' => true, 'admin' => false],
            ['area' => 'Operations', 'permission' => 'Create platform announcements', 'owner' => true, 'admin' => true],
            ['area' => 'Operations', 'permission' => 'View activity center and audit details', 'owner' => true, 'admin' => true],
            ['area' => 'Operations', 'permission' => 'View raw audit log', 'owner' => true, 'admin' => false],
            ['area' => 'Operations', 'permission' => 'View permissions matrix and system health', 'owner' => true, 'admin' => true],
        ];
    }
}
