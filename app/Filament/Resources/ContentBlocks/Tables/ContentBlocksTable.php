<?php

namespace App\Filament\Resources\ContentBlocks\Tables;

use App\Models\ContentBlock;
use App\Models\PublicContentBlock;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ContentBlocksTable
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

                TextColumn::make('category')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ContentBlock::CATEGORIES[$state] ?? $state)
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
                    ->label('Proven')
                    ->boolean(),

                TextColumn::make('usage_count')
                    ->label('Used')
                    ->numeric()
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('updated_at')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')->options(ContentBlock::CATEGORIES),
                SelectFilter::make('ka_action')->label('Action')->options(ContentBlock::KA_ACTIONS),
                SelectFilter::make('language')->options(ContentBlock::LANGUAGES),
                TernaryFilter::make('is_proven')->label('Proven only'),
            ])
            ->recordActions([
                // Publish — ascuns pe blocurile importate din biblioteca publica.
                Action::make('publish')
                    ->label('Publish')
                    ->icon('heroicon-o-globe-alt')
                    ->color('success')
                    ->visible(fn (ContentBlock $record) => blank($record->imported_from_public_id))
                    ->modalHeading('Publish to public library')
                    ->modalDescription('A public copy will be shared with all users. You stay the author and can edit or remove it later from the Public Library.')
                    ->form(function (ContentBlock $record) {
                        if ($record->is_proven && blank($record->source_note)) {
                            return [
                                TextInput::make('source_note')
                                    ->label('Source')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g. Approved KA152 youth exchange, 2025')
                                    ->helperText('Required because this block is marked as proven.'),
                            ];
                        }
                        return [];
                    })
                    ->action(function (ContentBlock $record, array $data): void {
                        $sourceNote = $record->source_note ?: ($data['source_note'] ?? null);

                        PublicContentBlock::create([
                            'user_id'             => auth()->id(),
                            'origin_workspace_id' => $record->workspace_id,
                            'title'               => $record->title,
                            'category'            => $record->category,
                            'ka_action'           => $record->ka_action,
                            'language'            => $record->language,
                            'body'                => $record->body,
                            'tags'                => $record->tags,
                            'is_proven'           => $record->is_proven,
                            'source_note'         => $sourceNote,
                            'import_count'        => 0,
                        ]);

                        Notification::make()
                            ->title('Published to public library')
                            ->body('Other users can now find and import this block.')
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
                ReplicateAction::make()
                    ->excludeAttributes(['usage_count'])
                    ->beforeReplicaSaved(function (ContentBlock $replica): void {
                        $replica->title = $replica->title . ' (copy)';
                        $replica->usage_count = 0;
                        $replica->imported_from_public_id = null;
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc')
            ->emptyStateHeading('No content blocks yet')
            ->emptyStateDescription('Save paragraphs you reuse across applications — organisation background, methodology, safety, dissemination — and insert them while writing.');
    }
}