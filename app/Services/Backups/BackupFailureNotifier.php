<?php

namespace App\Services\Backups;

use App\Models\User;
use Filament\Notifications\Notification;
use Throwable;

class BackupFailureNotifier
{
    public function send(string $title, string $detail): void
    {
        try {
            User::query()
                ->whereNull('archived_at')
                ->whereIn('role', [User::ROLE_PLATFORM_OWNER, User::ROLE_PLATFORM_ADMIN, User::ROLE_ADMIN])
                ->each(function (User $user) use ($title, $detail): void {
                    Notification::make()
                        ->title($title)
                        ->body($detail)
                        ->danger()
                        ->sendToDatabase($user, isEventDispatched: true);
                });
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
