<?php

namespace App\Filament\Resources\ContentBlocks;

use App\Filament\Resources\ContentBlocks\Pages\CreateContentBlock;
use App\Filament\Resources\ContentBlocks\Pages\EditContentBlock;
use App\Filament\Resources\ContentBlocks\Pages\ListContentBlocks;
use App\Filament\Resources\ContentBlocks\Schemas\ContentBlockForm;
use App\Filament\Resources\ContentBlocks\Tables\ContentBlocksTable;
use App\Models\ContentBlock;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ContentBlockResource extends Resource
{
    protected static ?string $model = ContentBlock::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;
    protected static string|\UnitEnum|null $navigationGroup = 'Tools';
    protected static ?string $navigationLabel = 'Content Library';
    protected static ?string $modelLabel = 'content block';
    protected static ?string $pluralModelLabel = 'content blocks';
    protected static ?string $recordTitleAttribute = 'title';

    // Scope every query to the active workspace via this relationship.
    protected static ?string $tenantOwnershipRelationshipName = 'workspace';

    public static function form(Schema $schema): Schema { return ContentBlockForm::configure($schema); }
    public static function table(Table $table): Table { return ContentBlocksTable::configure($table); }

    public static function getPages(): array
    {
        return [
            'index'  => ListContentBlocks::route('/'),
            'create' => CreateContentBlock::route('/create'),
            'edit'   => EditContentBlock::route('/{record}/edit'),
        ];
    }
}
