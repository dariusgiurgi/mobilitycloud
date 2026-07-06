<?php

namespace App\Filament\Resources\PublicContentBlocks\Pages;

use App\Filament\Pages\PublicLibrary;
use App\Filament\Resources\PublicContentBlocks\PublicContentBlockResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePublicContentBlock extends CreateRecord
{
    protected static string $resource = PublicContentBlockResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['origin_workspace_id'] = null;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return PublicLibrary::getUrl();
    }
}
