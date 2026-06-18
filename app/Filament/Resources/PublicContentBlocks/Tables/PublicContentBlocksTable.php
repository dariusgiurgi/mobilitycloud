<?php

namespace App\Filament\Resources\PublicContentBlocks\Tables;

use App\Models\ContentBlock;
use App\Models\PublicContentBlock;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PublicContentBlocksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->wrap()
                    ->weight('medium')
                    ->limit(60),

                TextColumn::make('author.name')
                    ->label('By')
                    ->toggleable(),

                TextColumn::make('category')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => PublicContentBlock::CATEGORIES[$state] ?? $state)
                    ->sortable(),

                TextColumn::make('ka_action')
                    ->label('Action')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => strtoupper($state))
                    ->color('gray'),

                TextColumn::make('language')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => strtoupper($state))
                    ->color('gray'),

                IconColumn::make('is_proven')
                    ->label('Verified')
                    ->boolean(),

                TextColumn::make('import_count')
                    ->label('Imports')
                    ->numeric()
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')->options(PublicContentBlock::CATEGORIES),
                SelectFilter::make('ka_action')->label('Action')->options(PublicContentBlock::KA_ACTIONS),
                SelectFilter::make('language')->options(PublicContentBlock::LANGUAGES),
                TernaryFilter::make('is_proven')->label('Verified only'),
            ])
            ->recordActions([
                // Oricine poate importa un bloc public in biblioteca personala.
                Action::make('import')
                    ->label('Import')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Import to your library')
                    ->modalDescription('A personal copy will be added to your workspace Content Library. You can edit it freely afterwards.')
                    ->action(function (PublicContentBlock $record) {
                        $workspace = Filament::getTenant();
                        if (! $workspace) return;

                        ContentBlock::create([
                            'workspace_id' => $workspace->id,
                            'title'        => $record->title,
                            'category'     => $record->category,
                            'ka_action'    => $record->ka_action,
                            'language'     => $record->language,
                            'body'         => $record->body,
                            'tags'         => $record->tags,
                            'is_proven'    => $record->is_proven,
                            'source_note'  => $record->source_note,
                            'usage_count'  => 0,
                            'imported_from_public_id' => $block->id,
                        ]);

                        $record->increment('import_count');

                        Notification::make()
                            ->title('Imported to your Content Library')
                            ->success()
                            ->send();
                    }),

                // Editare/stergere doar pentru autor.
                EditAction::make()
                    ->visible(fn (PublicContentBlock $record) => $record->isOwnedBy(auth()->user())),
                DeleteAction::make()
                    ->visible(fn (PublicContentBlock $record) => $record->isOwnedBy(auth()->user())),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => false), // dezactivam stergerea in masa in biblioteca publica
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No public blocks yet')
            ->emptyStateDescription('Publish blocks from your Content Library to share them with everyone.');
    }
}