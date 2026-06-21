<?php

namespace App\Filament\Resources\PublicContentBlocks\Schemas;

use App\Models\PublicContentBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PublicContentBlockForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Block')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Select::make('category')
                            ->options(PublicContentBlock::CATEGORIES)
                            ->required()
                            ->native(false)
                            ->default('other'),

                        Select::make('ka_action')
                            ->label('Action')
                            ->options(PublicContentBlock::KA_ACTIONS)
                            ->required()
                            ->native(false)
                            ->default('any'),

                        Select::make('language')
                            ->options(PublicContentBlock::LANGUAGES)
                            ->required()
                            ->native(false)
                            ->default('en'),

                        TagsInput::make('tags')
                            ->placeholder('Add tag, press Enter'),

                        Toggle::make('is_proven')
                            ->label('Proven (from an approved application)')
                            ->live()
                            ->inline(false),

                        TextInput::make('source_note')
                            ->label('Source')
                            ->maxLength(255)
                            ->placeholder('e.g. Approved KA152 youth exchange, 2025')
                            ->helperText('Required when the block is marked as proven.')
                            ->required(fn (callable $get) => (bool) $get('is_proven'))
                            ->columnSpanFull(),
                    ]),

                Section::make('Text')
                    ->schema([
                        Textarea::make('body')
                            ->required()
                            ->rows(14)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
