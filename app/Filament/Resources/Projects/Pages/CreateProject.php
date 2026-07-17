<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Pages\AccountSettings;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    public function mount(): void
    {
        $user = auth()->user();

        if ($user && ! $user->isUnlimitedAccount() && ! $user->hasBillingDetails()) {
            Notification::make()
                ->title('Billing details required')
                ->body('Complete your billing profile first: legal billing name, country and billing address. These details are required before project creation so MobilityCloud can issue the manual fiscal invoice after approval.')
                ->warning()
                ->send();

            $this->redirect(AccountSettings::getUrl());

            return;
        }

        parent::mount();
    }

    public function getTitle(): string
    {
        return 'Create a new project';
    }

    public function getSubheading(): ?string
    {
        return 'Start with the project identity and planning data. Application, budget, participants and documents will remain connected to this record.';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['owner_id'] = auth()->id();
        $data['workspace_id'] = null;
        $data['access_mode'] = 'restricted';
        $data['status'] = ! empty($data['create_as_approved']) ? 'approved' : 'writing';

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $approvedGrantDeclaration = $data['approved_grant_declaration'] ?? null;
        $createAsApproved = (bool) ($data['create_as_approved'] ?? false);

        $data['owner_id'] = auth()->id();
        $data['workspace_id'] = null;
        $data['status'] = $createAsApproved ? 'approved' : 'writing';

        unset($data['create_as_approved'], $data['approved_grant_declaration']);

        $record = new Project($data);
        $record->save();

        if ($createAsApproved) {
            $record->declareApprovedGrant($approvedGrantDeclaration, auth()->user());

            Notification::make()
                ->title('Approved project created')
                ->body($record->owner()?->isUnlimitedAccount()
                    ? 'The approved grant was locked. This unlimited account does not generate administration fees or fiscal invoice tasks.'
                    : 'The approved grant was locked and the platform fee was calculated. A fiscal invoice can now be issued manually.')
                ->success()
                ->send();
        }

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return ProjectResource::projectUrl($this->record);
    }

}
