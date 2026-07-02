<?php

namespace App\Filament\Resources\ContentBlocks\Pages;

use App\Filament\Pages\PublicLibrary;
use App\Filament\Resources\ContentBlocks\ContentBlockResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListContentBlocks extends ListRecords
{
    protected static string $resource = ContentBlockResource::class;

    public function getSubheading(): ?string
    {
        return 'Reusable application answers, examples and paragraphs organised for quick discovery.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('browsePublicLibrary')
                ->label('Browse public library')
                ->icon('heroicon-o-globe-alt')
                ->color('gray')
                ->url(fn (): string => PublicLibrary::getUrl()),
            CreateAction::make()
                ->label('New writing block')
                ->icon('heroicon-o-plus'),
        ];
    }
}
