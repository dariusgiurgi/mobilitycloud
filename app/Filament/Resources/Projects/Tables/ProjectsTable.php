<?php

namespace App\Filament\Resources\Projects\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Project')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->acronym),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft'     => 'gray',
                        'submitted' => 'warning',
                        'approved'  => 'info',
                        'activated' => 'success',
                        'completed' => 'primary',
                        'archived'  => 'gray',
                        default     => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('total_budget')
                    ->label('Budget')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('grant_ref')
                    ->label('Grant ref')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('—'),

                TextColumn::make('start_date')
                    ->date('d M Y')
                    ->toggleable()
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft'     => 'Draft',
                        'submitted' => 'Submitted',
                        'approved'  => 'Approved',
                        'activated' => 'Activated',
                        'completed' => 'Completed',
                        'archived'  => 'Archived',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
