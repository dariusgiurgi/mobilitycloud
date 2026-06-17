<?php

namespace App\Filament\Pages\Tenancy;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Pages\Tenancy\EditTenantProfile;

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
                TextInput::make('name')
                    ->label('Workspace name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('billing_name')
                    ->label('Billing name (company/NGO)')
                    ->maxLength(255),
                TextInput::make('billing_vat')
                    ->label('VAT / CUI')
                    ->maxLength(255),
                TextInput::make('billing_address')
                    ->label('Billing address')
                    ->maxLength(255),
                TextInput::make('billing_country')
                    ->label('Country code (RO, DE, ...)')
                    ->maxLength(2),
            ]);
    }
}
