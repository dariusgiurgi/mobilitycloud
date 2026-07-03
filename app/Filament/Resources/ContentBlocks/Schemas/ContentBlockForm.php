<?php

namespace App\Filament\Resources\ContentBlocks\Schemas;

use App\Models\ContentBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContentBlockForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Content')
                    ->description('Write one reusable idea or answer per block so it stays easy to adapt.')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g. Safety and protection of participants'),

                        Textarea::make('body')
                            ->label('Reusable text')
                            ->required()
                            ->rows(16)
                            ->helperText('Keep project-specific names, dates and figures out of reusable text whenever possible.'),
                    ]),

                Section::make('Classification')
                    ->description('These fields make the block easier to find while writing an application.')
                    ->columns(3)
                    ->schema([
                        Select::make('category')
                            ->options(ContentBlock::CATEGORIES)
                            ->required()
                            ->native(false)
                            ->default('other'),

                        Select::make('ka_action')
                            ->label('Compatible action')
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
                            ->placeholder('Add a tag and press Enter')
                            ->columnSpanFull(),
                    ]),

                Section::make('Quality and source')
                    ->description('Mark proven text only when it comes from an approved application.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_proven')
                            ->label('From an approved application')
                            ->live()
                            ->inline(false),

                        TextInput::make('source_note')
                            ->label('Source')
                            ->maxLength(255)
                            ->placeholder('e.g. Scoala de Jocuri, KA152 — approved 2025')
                            ->helperText('Required when the block is marked as proven.')
                            ->required(fn (callable $get): bool => (bool) $get('is_proven')),
                    ]),
            ]);
    }
}
