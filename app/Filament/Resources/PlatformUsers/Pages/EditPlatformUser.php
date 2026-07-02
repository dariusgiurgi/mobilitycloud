<?php

namespace App\Filament\Resources\PlatformUsers\Pages;

use App\Filament\Resources\PlatformUsers\PlatformUserResource;
use App\Support\PlatformAudit;
use Filament\Resources\Pages\EditRecord;

class EditPlatformUser extends EditRecord
{
    protected static string $resource = PlatformUserResource::class;

    protected array $originalAccountState = [];

    public function getSubheading(): ?string
    {
        $status = $this->record->is_suspended ? 'Suspended account' : 'Active account';

        return $status.' · '.$this->record->email.' · '.$this->record->workspaces()->count().' workspace(s)';
    }

    protected function authorizeAccess(): void
    {
        abort_unless(PlatformUserResource::canManageAccount($this->record), 403);

        parent::authorizeAccess();
    }

    protected function beforeSave(): void
    {
        $this->originalAccountState = $this->record->only([
            'name',
            'email',
            'role',
            'is_suspended',
            'suspension_category',
            'suspension_reason',
            'suspended_at',
            'must_change_password',
            'support_notes',
        ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['is_suspended'] ?? false) && ! $this->record->is_suspended) {
            $data['suspended_at'] = now();
            $data['suspended_by'] = auth()->id();
        }

        if (! ($data['is_suspended'] ?? false)) {
            $data['suspension_category'] = null;
            $data['suspension_reason'] = null;
            $data['suspended_at'] = null;
            $data['suspended_by'] = null;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $changes = [];

        foreach ($this->originalAccountState as $field => $oldValue) {
            $newValue = $this->record->{$field};

            if ($oldValue instanceof \DateTimeInterface) {
                $oldValue = $oldValue->format('Y-m-d H:i:s');
            }

            if ($newValue instanceof \DateTimeInterface) {
                $newValue = $newValue->format('Y-m-d H:i:s');
            }

            if ((string) $oldValue !== (string) $newValue) {
                $changes[$field] = [
                    'from' => $oldValue,
                    'to' => $newValue,
                ];
            }
        }

        PlatformAudit::log('account.updated', 'Updated account '.$this->record->email, $this->record, [
            'changes' => $changes,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
