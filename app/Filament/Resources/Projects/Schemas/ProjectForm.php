<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Enums\ProjectStatus;
use App\Support\ApplicationTemplates;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        $statusOptions = Collection::make(ProjectStatus::cases())
            ->mapWithKeys(fn (ProjectStatus $s) => [$s->value => $s->getLabel()])
            ->all();

        return $schema
            ->components([
                Section::make('Project identity')
                    ->description('Core information used across the application, exports and generated documents.')
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
                            ->maxLength(255)
                            ->placeholder('Assigned after approval'),
                        Textarea::make('description')
                            ->rows(3)
                            ->placeholder('Short internal description of the project')
                            ->columnSpanFull(),
                    ]),

                Section::make('Application setup')
                    ->description('Optional. Choose an official application template only when this project also needs a structured Writing workspace.')
                    ->columns(2)
                    ->schema([
                        Select::make('ka_action')
                            ->label('Application template')
                            ->options(ApplicationTemplates::list())
                            ->placeholder('No application template')
                            ->formatStateUsing(fn (?string $state): ?string => filled($state) ? ApplicationTemplates::normaliseKey($state) : null)
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? ApplicationTemplates::normaliseKey($state) : null)
                            ->searchable()
                            ->native(false)
                            ->nullable()
                            ->helperText('Leave empty for operational/manual projects. You can choose a template later from Writing → Template manager.'),
                    ]),

                Section::make('Timeline')
                    ->description('Project dates drive dashboard reminders; mobility dates determine participant age and attendance defaults.')
                    ->columns(2)
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('Project start'),
                        DatePicker::make('end_date')
                            ->label('Project end')
                            ->afterOrEqual('start_date'),
                        DatePicker::make('mobility_start_date')
                            ->label('Mobility start')
                            ->helperText('Used to determine which participants are minors.'),
                        DatePicker::make('mobility_end_date')
                            ->label('Mobility end')
                            ->afterOrEqual('mobility_start_date'),
                    ]),

                Section::make('Involved organisations')
                    ->description('Add the coordinator and partner organisations used in participant grouping and attendance sheets.')
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
                                    ->maxLength(50)
                                    ->placeholder('E00000000'),
                                Toggle::make('is_coordinator')
                                    ->label('Coordinator')
                                    ->inline(false)
                                    ->columnSpan(1),
                            ])
                            ->columns(5)
                            ->addActionLabel('Add organisation')
                            ->itemLabel(fn (array $state): ?string => ($state['name'] ?? null)
                                .(! empty($state['is_coordinator']) ? ' — Coordinator' : ''))
                            ->collapsible()
                            ->collapsed()
                            ->reorderable()
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ]),

                Section::make('Funding and taxation')
                    ->description('Requested funding is used before approval; the approved grant becomes the spending baseline after confirmation.')
                    ->columns(3)
                    ->schema([
                        TextInput::make('total_budget')
                            ->label('Total budget (€)')
                            ->numeric()
                            ->default(0)
                            ->prefix('€')
                            ->minValue(0)
                            ->required(),
                        TextInput::make('approved_budget')
                            ->label('Approved budget (€)')
                            ->numeric()
                            ->prefix('€')
                            ->minValue(0)
                            ->helperText('Leave empty until confirmed by the funder.'),
                        TextInput::make('first_tranche_pct')
                            ->label('1st tranche (%)')
                            ->numeric()
                            ->default(80)
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100),
                        TextInput::make('withholding_tax_rate')
                            ->label('Withholding tax (%)')
                            ->numeric()
                            ->default(10)
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->helperText('Applied when civil convention payment statements are generated.'),
                    ]),

                Section::make('Advanced controls')
                    ->description('Lifecycle overrides and expense numbering affect several project modules. Change them deliberately.')
                    ->columns(3)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Select::make('status')
                            ->options($statusOptions)
                            ->default('writing')
                            ->required()
                            ->native(false)
                            ->helperText('Normally changed from Overview.'),
                        TextInput::make('expense_prefix')
                            ->label('Expense prefix')
                            ->default('EXP')
                            ->maxLength(20)
                            ->regex('/^[A-Za-z0-9_-]+$/')
                            ->dehydrateStateUsing(fn (?string $state): string => Str::upper(trim($state ?: 'EXP')))
                            ->helperText('Letters, numbers, hyphens and underscores only.'),
                        Select::make('expense_pad_length')
                            ->label('Expense number padding')
                            ->options([2 => '2 digits', 3 => '3 digits', 4 => '4 digits', 5 => '5 digits', 6 => '6 digits'])
                            ->default(3)
                            ->native(false),
                    ]),
            ]);
    }
}
