<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Enums\ProjectStatus;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Collection;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        $statusOptions = Collection::make(ProjectStatus::cases())
            ->mapWithKeys(fn (ProjectStatus $s) => [$s->value => $s->getLabel()])
            ->all();

        return $schema
            ->components([
                Section::make('Project details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Project name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('acronym')
                            ->maxLength(255),
                        TextInput::make('grant_ref')
                            ->label('Grant reference')
                            ->maxLength(255),
                        Select::make('status')
                            ->options($statusOptions)
                            ->default('writing')
                            ->required()
                            ->native(false)
                            ->helperText('Manual override. Normally driven by the buttons on Overview.'),
                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Involved organisations')
                    ->schema([
                        Repeater::make('partner_orgs')
                            ->hiddenLabel()
                            ->schema([
                                TextInput::make('name')
                                    ->label('Organisation name')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(2),
                                TextInput::make('country')
                                    ->maxLength(100),
                                TextInput::make('oid')
                                    ->label('OID')
                                    ->maxLength(50),
                                Toggle::make('is_coordinator')
                                    ->label('Coordinator')
                                    ->inline(false)
                                    ->columnSpan(1),
                            ])
                            ->columns(5)
                            ->addActionLabel('Add organisation')
                            ->itemLabel(fn (array $state): ?string =>
                                ($state['name'] ?? null)
                                . (! empty($state['is_coordinator']) ? ' — Coordinator' : ''))
                            ->collapsible()
                            ->collapsed()
                            ->reorderable()
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ]),

                Section::make('Budget')
                    ->columns(3)
                    ->schema([
                        TextInput::make('total_budget')
                            ->label('Total budget (€)')
                            ->numeric()
                            ->default(0)
                            ->prefix('€')
                            ->required(),
                        TextInput::make('approved_budget')
                            ->label('Approved budget (€)')
                            ->numeric()
                            ->prefix('€')
                            ->helperText('Confirmed by funder'),
                        TextInput::make('first_tranche_pct')
                            ->label('1st tranche (%)')
                            ->numeric()
                            ->default(80)
                            ->suffix('%'),
                        TextInput::make('withholding_tax_rate')
                            ->label('Withholding tax (%)')
                            ->numeric()
                            ->default(10)
                            ->suffix('%'),
                    ]),

                Section::make('Timeline')
                    ->columns(2)
                    ->schema([
                        DatePicker::make('start_date'),
                        DatePicker::make('end_date'),
                    ]),

                Section::make('Expense code settings')
                    ->columns(2)
                    ->schema([
                        TextInput::make('expense_prefix')
                            ->label('Prefix')
                            ->default('EXP')
                            ->maxLength(20)
                            ->helperText('e.g. EXP, ERAS26'),
                        Select::make('expense_pad_length')
                            ->label('Padding')
                            ->options([2 => '2 digits', 3 => '3 digits', 4 => '4 digits', 5 => '5 digits', 6 => '6 digits'])
                            ->default(3),
                    ]),
            ]);
    }
}