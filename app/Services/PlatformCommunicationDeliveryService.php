<?php

namespace App\Services;

use App\Models\PlatformAnnouncement;
use App\Models\User;
use App\Support\PlatformAudit;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class PlatformCommunicationDeliveryService
{
    public function sendNotification(PlatformAnnouncement $communication): int
    {
        if (! $communication->sendsNotification()) {
            return 0;
        }

        $sent = 0;

        $this->recipientQuery($communication)
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($communication, &$sent): void {
                foreach ($users as $user) {
                    $notification = Notification::make()
                        ->title($communication->title)
                        ->body($communication->message)
                        ->viewData([
                            'kind' => 'platform_broadcast',
                            'communication_id' => $communication->id,
                            'severity' => $communication->severity,
                            'sent_by' => auth()->id(),
                            'sent_by_name' => auth()->user()?->name,
                            'sent_at' => now()->toIso8601String(),
                        ]);

                    match ($communication->severity) {
                        'critical' => $notification->danger(),
                        'maintenance', 'warning' => $notification->warning(),
                        default => $notification->info(),
                    };

                    $notification->sendToDatabase($user, isEventDispatched: true);
                    $sent++;
                }
            });

        $communication->forceFill([
            'notification_sent_at' => now(),
            'notification_sent_count' => $sent,
        ])->saveQuietly();

        PlatformAudit::log('communication.notification_sent', 'Sent platform communication notification '.$communication->title, $communication, [
            'audience' => $communication->audience,
            'sent_count' => $sent,
        ]);

        return $sent;
    }

    private function recipientQuery(PlatformAnnouncement $communication): Builder
    {
        $query = User::query()
            ->whereNull('archived_at')
            ->whereNotNull('email_verified_at');

        return match ($communication->audience) {
            'platform_admins' => $query->whereIn('role', [User::ROLE_PLATFORM_OWNER, User::ROLE_PLATFORM_ADMIN, User::ROLE_ADMIN]),
            'client_users' => $query->whereNotIn('role', [User::ROLE_PLATFORM_OWNER, User::ROLE_PLATFORM_ADMIN, User::ROLE_ADMIN, User::ROLE_SUPERVISOR]),
            'plans' => $query->whereIn('plan', $communication->plans ?? []),
            'selected_users' => $query->whereIn('id', collect($communication->target_user_ids ?? [])->map(fn ($id): int => (int) $id)->filter()->values()->all()),
            default => $query,
        };
    }
}
