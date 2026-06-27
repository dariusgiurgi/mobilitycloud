<?php

namespace App\Filament\Resources\PlatformUsers\Pages;

use App\Filament\Resources\PlatformUsers\PlatformUserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPlatformUsers extends ListRecords
{
    protected static string $resource = PlatformUserResource::class;

    public function getSubheading(): ?string
    {
        return 'All platform accounts, workspace memberships, plan exposure and support flags.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Create account')
                ->visible(fn (): bool => auth()->user()?->canManagePlatformAdmins() ?? false),
        ];
    }
}
