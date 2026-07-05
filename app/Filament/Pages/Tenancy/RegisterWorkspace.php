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
        return 'Create your first workspace';
    }

    public function getTitle(): string
    {
        return 'Create your first workspace';
    }

    public function getSubheading(): ?string
    {
        return 'Your account is ready. Create the organisation workspace that will own the subscription and the projects you manage.';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Organisation workspace')
                    ->description('The workspace is the billing and ownership container. Projects stay inside it, and collaborators are invited directly to the projects they need.')
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
