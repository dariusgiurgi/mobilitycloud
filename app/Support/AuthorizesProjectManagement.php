<?php

namespace App\Support;

trait AuthorizesProjectManagement
{
    protected function authorizeProjectManagement(): void
    {
        abort_unless(
            isset($this->record) && $this->record->canBeManagedBy(auth()->user()),
            403
        );
    }
}
