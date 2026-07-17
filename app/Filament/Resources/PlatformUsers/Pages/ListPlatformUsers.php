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

    public function getSubheading(): ?string
    {
        return 'All platform accounts, project ownership, plan exposure and support flags.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Create account')
                ->visible(fn (): bool => auth()->user()?->canManagePlatformAdmins() ?? false),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'active' => Tab::make('Active')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNull('archived_at')),
            'attention' => Tab::make('Needs attention')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->whereNull('archived_at')
                    ->where(function (Builder $query): void {
                        $query
                            ->where('is_suspended', true)
                            ->orWhere('must_change_password', true)
                            ->orWhereNull('email_verified_at');
                    })),
            'pending_verification' => Tab::make('Pending verification')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->whereNull('archived_at')
                    ->whereNull('email_verified_at')),
            'staff' => Tab::make('Platform staff')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->whereNull('archived_at')
                    ->whereIn('role', [User::ROLE_PLATFORM_OWNER, User::ROLE_PLATFORM_ADMIN, User::ROLE_ADMIN, User::ROLE_SUPERVISOR])),
            'archived' => Tab::make('Archived')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNotNull('archived_at')),
        ];
    }
}
