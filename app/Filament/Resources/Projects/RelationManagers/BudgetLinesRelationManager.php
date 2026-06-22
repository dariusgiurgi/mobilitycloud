<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BudgetLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'budgetLines';

    protected static ?string $title = 'Budget lines';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Title')
                    ->required()
                    ->maxLength(255),
                TextInput::make('emoji')
                    ->label('Emoji')
                    ->maxLength(8)
                    ->placeholder('✈️'),
                ColorPicker::make('color')
                    ->label('Color'),
                TextInput::make('allocated_budget')
                    ->label('Allocated budget (€)')
                    ->numeric()
                    ->default(0)
                    ->prefix('€')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('emoji')
                    ->label('')
                    ->size('lg'),

                TextColumn::make('title')
                    ->label('Budget line')
                    ->weight('bold')
                    ->searchable(),

                ColorColumn::make('color')
                    ->label('Color'),

                TextColumn::make('allocated_budget')
                    ->label('Allocated')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('spent')
                    ->label('Spent')
                    ->money('EUR')
                    ->state(fn ($record) => $record->spent),

                TextColumn::make('remaining')
                    ->label('Remaining')
                    ->money('EUR')
                    ->state(fn ($record) => $record->remaining)
                    ->color(fn ($record) => $record->remaining < 0 ? 'danger' : 'success'),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->headerActions([
                CreateAction::make()
                    ->label('Add budget line'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
