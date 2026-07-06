<?php

namespace App\Filament\Resources\PublicContentBlocks\Pages;

use App\Filament\Pages\PublicLibrary;
use App\Filament\Resources\PublicContentBlocks\PublicContentBlockResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EditPublicContentBlock extends EditRecord
{
    protected static string $resource = PublicContentBlockResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        // Doar autorul poate edita. Oricine altcineva primeste 403.
        if (! $this->record->isOwnedBy(Auth::user())) {
            throw new AccessDeniedHttpException('You can only edit blocks you published.');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->successRedirectUrl(fn () => PublicLibrary::getUrl()),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return PublicLibrary::getUrl();
    }
}
