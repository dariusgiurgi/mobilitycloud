<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Workspace;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Pages\Tenancy\RegisterTenant;

class RegisterWorkspace extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Create workspace';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Workspace name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    protected function handleRegistration(array $data): Workspace
    {
        $workspace = Workspace::create($data);

        $workspace->users()->attach(auth()->id(), [
            'role'      => 'owner',
            'joined_at' => now(),
        ]);

        return $workspace;
    }
}
