<?php

namespace App\Filament\Resources\PlatformUsers\Pages;

use App\Filament\Resources\PlatformUsers\PlatformUserResource;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPlatformUsers extends ListRecords
{
    protected static string $resource = PlatformUserResource::class;

    private ?object $cachedCategoryCounts = null;

    public function getSubheading(): ?string
    {
        return 'Find people and organisations. Manage access, billing and project fees.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Create account')
                ->icon('heroicon-o-plus')
                ->visible(fn (): bool => auth()->user()?->canManagePlatformAdmins() ?? false),
        ];
    }

    public function getTabs(): array
    {
        $available = 'archived_at IS NULL AND deleted_at IS NULL';
        $categories = [
            'all' => ['All accounts', 'heroicon-o-users', '1 = 1'],
            'active' => ['Active', 'heroicon-o-check-circle', $available.' AND is_suspended = 0 AND must_change_password = 0 AND email_verified_at IS NOT NULL AND account_deletion_status IS NULL'],
            'attention' => ['Needs attention', 'heroicon-o-exclamation-circle', '('.$available.' AND (is_suspended = 1 OR must_change_password = 1 OR email_verified_at IS NULL OR account_deletion_status IS NOT NULL)) OR account_deletion_status = ?', [User::ACCOUNT_DELETION_FAILED]],
            'pending_verification' => ['Unverified', 'heroicon-o-envelope', $available.' AND email_verified_at IS NULL'],
            'staff' => ['Platform staff', 'heroicon-o-shield-check', $available.' AND role IN (?, ?, ?, ?)', [User::ROLE_PLATFORM_OWNER, User::ROLE_PLATFORM_ADMIN, User::ROLE_ADMIN, User::ROLE_SUPERVISOR]],
            'archived' => ['Archived', 'heroicon-o-archive-box', 'archived_at IS NOT NULL OR deleted_at IS NOT NULL'],
        ];

        $tabs = [];
        foreach ($categories as $key => [$label, $icon, $condition]) {
            $bindings = $categories[$key][3] ?? [];
            $tabs[$key] = Tab::make($label)
                ->icon($icon)
                ->badge(fn (): int => $this->categoryCount($key, $categories))
                ->badgeTooltip('Accounts in this category, before search and filters')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where(fn (Builder $query) => $query->whereRaw($condition, $bindings)));
        }

        return $tabs;
    }

    private function categoryCount(string $key, array $categories): int
    {
        // Defer the single aggregate query until rendering, after any account action.
        if ($this->cachedCategoryCounts === null) {
            $query = User::query()->withTrashed();
            foreach ($categories as $categoryKey => $category) {
                $query->selectRaw('SUM(CASE WHEN ('.$category[2].') THEN 1 ELSE 0 END) AS '.$categoryKey.'_count', $category[3] ?? []);
            }
            $this->cachedCategoryCounts = $query->toBase()->first();
        }

        return (int) ($this->cachedCategoryCounts->{$key.'_count'} ?? 0);
    }
}
