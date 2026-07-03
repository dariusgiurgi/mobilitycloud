<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Workspace;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

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
                Section::make('Your organisation workspace')
                    ->description('A workspace keeps one organisation\'s projects, participants, documents and reusable content together. You can complete the legal details later.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Workspace name')
                            ->required()
                            ->maxLength(255)
                            ->autofocus()
                            ->placeholder('e.g. Scoala de Jocuri'),
                    ]),
            ]);
    }

    protected function handleRegistration(array $data): Workspace
    {
        $workspace = Workspace::create($data);

        $workspace->users()->attach(auth()->id(), [
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        return $workspace;
    }
}
