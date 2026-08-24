<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    protected bool $createAsApproved = false;

    protected mixed $approvedGrantDeclaration = null;

    protected mixed $approvedGrantProofUpload = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->createAsApproved = (bool) ($data['create_as_approved'] ?? false);
        $this->approvedGrantDeclaration = $data['approved_grant_declaration'] ?? null;
        $this->approvedGrantProofUpload = $data['approved_grant_proof_upload'] ?? null;

        unset($data['create_as_approved'], $data['approved_grant_declaration'], $data['approved_grant_proof_upload']);

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
