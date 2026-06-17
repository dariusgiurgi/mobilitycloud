<?php

namespace App\Filament\Resources\ContentBlocks\Schemas;

use App\Models\ContentBlock;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TagsInput;

class ContentBlockForm
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
                            ->columnSpanFull()
                            ->placeholder('e.g. Safety & protection of participants'),

                        Select::make('category')
                            ->options(ContentBlock::CATEGORIES)
                            ->required()
                            ->native(false)
                            ->default('other'),

                        Select::make('ka_action')
                            ->label('Action')
                            ->options(ContentBlock::KA_ACTIONS)
                            ->required()
                            ->native(false)
                            ->default('any'),

                        Select::make('language')
                            ->options(ContentBlock::LANGUAGES)
                            ->required()
                            ->native(false)
                            ->default('en'),

                        TagsInput::make('tags')
                            ->placeholder('Add tag, press Enter'),

                        Toggle::make('is_proven')
                            ->label('From an approved application')
                            ->inline(false),

                        TextInput::make('source_note')
                            ->label('Source')
                            ->maxLength(255)
                            ->placeholder('e.g. Roots in Motion (KA152) — approved 2025')
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
