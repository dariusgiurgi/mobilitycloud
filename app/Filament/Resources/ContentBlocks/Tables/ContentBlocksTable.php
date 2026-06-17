<?php

namespace App\Filament\Resources\ContentBlocks\Tables;

use App\Models\ContentBlock;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
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
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')->options(ContentBlock::CATEGORIES),
                SelectFilter::make('ka_action')->label('Action')->options(ContentBlock::KA_ACTIONS),
                SelectFilter::make('language')->options(ContentBlock::LANGUAGES),
                TernaryFilter::make('is_proven')->label('Proven only'),
            ])
            ->recordActions([
                EditAction::make(),
                ReplicateAction::make()
                    ->excludeAttributes(['usage_count'])
                    ->beforeReplicaSaved(function (ContentBlock $replica): void {
                        $replica->title = $replica->title . ' (copy)';
                        $replica->usage_count = 0;
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
