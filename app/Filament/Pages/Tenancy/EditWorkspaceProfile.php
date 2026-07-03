<?php

namespace App\Filament\Pages\Tenancy;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class EditWorkspaceProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return 'Workspace settings';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Workspace identity')
                    ->description('The workspace name appears in navigation and identifies the organisation environment.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Workspace name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g. Scoala de Jocuri'),
                    ]),

                Section::make('Legal and billing details')
                    ->description('These details pre-fill official project documents and civil conventions. Keep them legally accurate.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('billing_name')
                            ->label('Legal organisation name')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('billing_vat')
                            ->label('VAT / registration number')
                            ->maxLength(255),
                        TextInput::make('billing_country')
                            ->label('Country code')
                            ->placeholder('RO')
                            ->length(2)
                            ->regex('/^[A-Za-z]{2}$/')
                            ->dehydrateStateUsing(fn (?string $state): ?string => $state ? Str::upper(trim($state)) : null),
                        Textarea::make('billing_address')
                            ->label('Registered address')
                            ->rows(3)
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
