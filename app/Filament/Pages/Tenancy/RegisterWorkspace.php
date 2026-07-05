<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Workspace;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RegisterWorkspace extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Set up your organisation';
    }

    public function getTitle(): string
    {
        return 'Set up your organisation';
    }

    public function getSubheading(): ?string
    {
        return 'Your account is ready. Add the organisation that will own the subscription and the projects you manage.';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Organisation details')
                    ->description('This organisation owns the subscription. Projects stay inside it, and collaborators are invited directly to the projects they need.')
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

    public function getRegisterFormAction(): Action
    {
        return parent::getRegisterFormAction()
            ->label('Continue');
    }
}
