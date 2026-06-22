<?php

namespace App\Filament\Resources\PublicContentBlocks\Pages;

use App\Filament\Resources\PublicContentBlocks\PublicContentBlockResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPublicContentBlocks extends ListRecords
{
    protected static string $resource = PublicContentBlockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('New public block'),
        ];
    }
}
