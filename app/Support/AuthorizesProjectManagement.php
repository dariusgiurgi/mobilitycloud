<?php

namespace App\Support;

trait AuthorizesProjectManagement
{
    protected function authorizeProjectAccess(): void
    {
        abort_unless(
            isset($this->record) && $this->record->canBeAccessedBy(auth()->user()),
            403
        );
    }

    protected function authorizeProjectManagement(): void
    {
        abort_unless(
            isset($this->record) && $this->record->canBeCollaboratedOnBy(auth()->user()),
            403
        );
    }

    protected function authorizeApplicationEditing(): void
    {
        abort_unless(
            isset($this->record) && $this->record->canEditApplicationBy(auth()->user()),
            403
        );
    }

    protected function authorizeManagementModuleAccess(): void
    {
        abort_unless(
            isset($this->record) && $this->record->canViewManagementModulesBy(auth()->user()),
            404
        );
    }

    protected function authorizeManagementModuleMutation(): void
    {
        abort_unless(
            isset($this->record) && $this->record->canManageManagementModulesBy(auth()->user()),
            403
        );
    }
}
