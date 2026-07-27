<?php

namespace App\Filament\Resources\PlatformUsers\Pages;

use App\Filament\Resources\PlatformUsers\PlatformUserResource;
use App\Support\PlatformAudit;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditPlatformUser extends EditRecord
{
    protected static string $resource = PlatformUserResource::class;

    protected array $originalAccountState = [];

    private bool $isAutosaving = false;

    public function getSubheading(): ?string
    {
        $status = $this->record->is_suspended ? 'Suspended account' : 'Active account';

        return $status.' · '.$this->record->email.' · '
            .$this->record->ownedProjects()->count().' owned project(s), '
            .$this->record->projects()->count().' shared project access';
    }

    protected function authorizeAccess(): void
    {
        abort_unless(PlatformUserResource::canManageAccount($this->record), 403);

        parent::authorizeAccess();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getFormActions(): array
    {
        return [];
    }

    public function updated(string $propertyName, mixed $value = null): void
    {
        if (! str_starts_with($propertyName, 'data.')) {
            return;
        }

        if ($this->isAutosaving || ! isset($this->record)) {
            return;
        }

        $this->isAutosaving = true;

        try {
            $this->save(shouldRedirect: false, shouldSendSavedNotification: false);

            Notification::make()
                ->title('Account saved automatically')
                ->success()
                ->send();
        } catch (ValidationException $exception) {
            throw $exception;
        } finally {
            $this->isAutosaving = false;
        }
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
            'plan',
            'subscription_status',
            'billing_name',
            'billing_vat',
            'billing_country',
            'billing_address',
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
