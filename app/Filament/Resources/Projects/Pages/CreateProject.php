<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Pages\AccountSettings;
use App\Filament\Resources\Projects\ProjectResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    protected static bool $canCreateAnother = false;

    protected bool $createAsApproved = false;

    protected mixed $approvedGrantDeclaration = null;

    protected mixed $approvedGrantProofUpload = null;

    public function mount(): void
    {
        if (! ProjectResource::canCreate()) {
            $this->redirect(AccountSettings::getUrl());

            return;
        }

        parent::mount();
    }

    public function form(Schema $schema): Schema
    {
        return parent::form($schema)->columns(1);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->createAsApproved = ($data['project_entry_mode'] ?? 'application') === 'approved';
        $this->approvedGrantDeclaration = $data['approved_grant_declaration'] ?? null;
        $this->approvedGrantProofUpload = $data['approved_grant_proof_upload'] ?? null;

        unset($data['project_entry_mode'], $data['approved_grant_declaration'], $data['approved_grant_proof_upload']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if (! $this->createAsApproved) {
            return;
        }

        $proofPath = $this->normaliseUploadedPath($this->approvedGrantProofUpload);

        if ($proofPath) {
            $this->record->forceFill([
                'approved_grant_proof_path' => $proofPath,
                'approved_grant_proof_disk' => 'local',
                'approved_grant_proof_original_name' => basename($proofPath),
                'approved_grant_proof_uploaded_at' => now(),
            ])->save();

            $this->record->refresh();
        }

        $this->record->declareApprovedGrant($this->approvedGrantDeclaration, auth()->user());
    }

    protected function getRedirectUrl(): string
    {
        return $this->record->isWritingStage()
            ? ProjectResource::getUrl('write', ['record' => $this->record])
            : ProjectResource::getUrl('overview', ['record' => $this->record]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return $this->createAsApproved
            ? 'Approved project created — implementation is ready'
            : 'Project created — choose an application template to begin';
    }

    private function normaliseUploadedPath(mixed $state): ?string
    {
        if (is_string($state) && $state !== '') {
            return $state;
        }

        if (is_array($state)) {
            $first = Arr::first($state);

            return is_string($first) && $first !== '' ? $first : null;
        }

        return null;
    }
}
