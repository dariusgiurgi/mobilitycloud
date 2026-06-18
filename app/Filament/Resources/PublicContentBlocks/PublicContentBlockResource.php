<?php

namespace App\Filament\Resources\PublicContentBlocks;

use App\Filament\Resources\PublicContentBlocks\Pages\ListPublicContentBlocks;
use App\Filament\Resources\PublicContentBlocks\Pages\CreatePublicContentBlock;
use App\Filament\Resources\PublicContentBlocks\Pages\EditPublicContentBlock;
use App\Filament\Resources\PublicContentBlocks\Schemas\PublicContentBlockForm;
use App\Filament\Resources\PublicContentBlocks\Tables\PublicContentBlocksTable;
use App\Models\PublicContentBlock;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PublicContentBlockResource extends Resource
{
    protected static ?string $model = PublicContentBlock::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;
    protected static string|\UnitEnum|null $navigationGroup = 'Community';
    protected static ?string $navigationLabel = 'Public Library';
    protected static ?string $modelLabel = 'public block';
    protected static ?string $pluralModelLabel = 'public blocks';
    protected static ?string $recordTitleAttribute = 'title';

    // NU e tenant-scoped: biblioteca publica e comuna tuturor workspace-urilor.
    public static function isScopedToTenant(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema { return PublicContentBlockForm::configure($schema); }
    public static function table(Table $table): Table { return PublicContentBlocksTable::configure($table); }

    public static function getPages(): array
    {
        return [
            'index'  => ListPublicContentBlocks::route('/'),
            'create' => CreatePublicContentBlock::route('/create'),
            'edit'   => EditPublicContentBlock::route('/{record}/edit'),
        ];
    }
}