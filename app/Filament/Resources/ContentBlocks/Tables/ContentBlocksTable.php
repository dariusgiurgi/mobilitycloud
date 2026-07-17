<?php

namespace App\Filament\Resources\ContentBlocks\Tables;

use App\Models\ContentBlock;
use App\Models\PublicContentBlock;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ContentBlocksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Content')
                    ->searchable()
                    ->wrap()
                    ->weight('medium')
                    ->limit(60)
                    ->description(fn (ContentBlock $record): string => Str::limit(strip_tags($record->body), 110)),

                TextColumn::make('category')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ContentBlock::CATEGORIES[$state] ?? $state)
                    ->sortable(),

                TextColumn::make('ka_action')
                    ->label('Compatibility')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'any' ? 'Any action' : strtoupper($state))
                    ->color('gray'),

                TextColumn::make('language')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => strtoupper($state))
                    ->color('gray'),

                IconColumn::make('is_proven')
                    ->label('Proven')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray'),

                TextColumn::make('imported_from_public_id')
                    ->label('Origin')
                    ->getStateUsing(fn (ContentBlock $record): string => $record->imported_from_public_id ? 'Public library' : 'Personal')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Public library' ? 'info' : 'gray'),

                TextColumn::make('usage_count')
                    ->label('Used')
                    ->numeric()
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options(ContentBlock::CATEGORIES),
                SelectFilter::make('ka_action')
                    ->label('Compatibility')
                    ->options(ContentBlock::KA_ACTIONS),
                SelectFilter::make('language')
                    ->options(ContentBlock::LANGUAGES),
                TernaryFilter::make('is_proven')
                    ->label('Proven content'),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('publish')
                        ->label('Publish to community')
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
                                'user_id' => auth()->id(),
                                'title' => $record->title,
                                'category' => $record->category,
                                'ka_action' => $record->ka_action,
                                'language' => $record->language,
                                'body' => $record->body,
                                'tags' => $record->tags,
                                'is_proven' => $record->is_proven,
                                'source_note' => $sourceNote,
                                'import_count' => 0,
                            ]);

                            Notification::make()
                                ->title('Published to public library')
                                ->body('Other users can now find and import this block.')
                                ->success()
                                ->send();
                        }),

                    ReplicateAction::make()
                        ->label('Duplicate')
                        ->excludeAttributes(['usage_count'])
                        ->beforeReplicaSaved(function (ContentBlock $replica): void {
                            $replica->title = $replica->title.' (copy)';
                            $replica->usage_count = 0;
                            $replica->imported_from_public_id = null;
                        }),

                    DeleteAction::make(),
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-horizontal'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc')
            ->emptyStateHeading('No writing blocks yet')
            ->emptyStateDescription('Save reusable answers here, or import a proven example from the Public Library.');
    }
}
