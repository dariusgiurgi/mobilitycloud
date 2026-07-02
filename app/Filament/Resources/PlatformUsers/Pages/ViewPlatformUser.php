<?php

namespace App\Filament\Resources\PlatformUsers\Pages;

use App\Filament\Resources\PlatformUsers\PlatformUserResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPlatformUser extends ViewRecord
{
    protected static string $resource = PlatformUserResource::class;

    public function getTitle(): string
    {
        return $this->record->name ?: $this->record->email;
    }

    public function getSubheading(): ?string
    {
        $status = $this->record->archived_at
            ? 'Archived account'
            : ($this->record->is_suspended ? 'Suspended account' : 'Active account');

        return $status.' · '.$this->record->email.' · '.$this->record->workspaces()->count().' workspace(s)';
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (): bool => PlatformUserResource::canManageAccount($this->record) && blank($this->record->archived_at)),
        ];
    }
}
