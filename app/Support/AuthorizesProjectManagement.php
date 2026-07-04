<?php

namespace App\Support;

trait AuthorizesProjectManagement
{
    protected function authorizeProjectManagement(): void
    {
        abort_unless(
            isset($this->record) && $this->record->canBeCollaboratedOnBy(auth()->user()),
            403
        );
    }
}
