<?php

namespace App\Services;

use App\Filament\Resources\PlatformUsers\PlatformUserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SubscriptionAlertService
{
    public function dispatch(): int
    {
        $recipients = $this->recipients();

        if ($recipients->isEmpty()) {
            return 0;
        }

        $sent = 0;

        User::query()
            ->where('role', User::ROLE_USER)
            ->orderBy('id')
            ->chunkById(100, function (Collection $accounts) use ($recipients, &$sent): void {
                foreach ($accounts as $account) {
                    $sent += $this->dispatchForAccount($account, $recipients);
                }
            });

        return $sent;
    }

    /**
     * @param Collection<int, User> $recipients
     */
    private function dispatchForAccount(User $account, Collection $recipients): int
    {
        $sent = 0;

        foreach ($this->alertsFor($account) as $alert) {
            if ($account->{$alert['timestamp']}) {
                continue;
            }

            $this->send($recipients, $alert['title'], $alert['body'], $alert['level']);

            $account->forceFill([$alert['timestamp'] => now()])->saveQuietly();
            $sent++;
        }

        return $sent;
    }

    /**
     * @return array<int, array{timestamp: string, title: string, body: string, level: string}>
     */
    private function alertsFor(User $account): array
    {
        $alerts = [];
        $name = $account->name ?: $account->email;

        if ($account->subscription_ends_at) {
            if ($account->subscription_ends_at->isPast() || $account->subscription_status === 'expired') {
                $alerts[] = [
                    'timestamp' => 'subscription_expired_alerted_at',
                    'title' => 'Account access expired',
                    'body' => $name.' account access ended '.$account->subscription_ends_at->diffForHumans().'.',
                    'level' => 'danger',
                ];
            } elseif ($account->subscription_ends_at->between(now(), now()->addDays(14))) {
                $alerts[] = [
                    'timestamp' => 'subscription_ending_alerted_at',
                    'title' => 'Account access ending soon',
                    'body' => $name.' account access ends '.$account->subscription_ends_at->diffForHumans().'.',
                    'level' => 'warning',
                ];
            }
        } elseif ($account->subscription_status === 'expired') {
            $alerts[] = [
                'timestamp' => 'subscription_expired_alerted_at',
                'title' => 'Account access expired',
                'body' => $name.' is marked as expired.',
                'level' => 'danger',
            ];
        }

        if ($account->access_override_reason && $account->access_override_ends_at?->between(now(), now()->addDays(7))) {
            $alerts[] = [
                'timestamp' => 'manual_access_ending_alerted_at',
                'title' => 'Manual access ending soon',
                'body' => $name.' manual access ends '.$account->access_override_ends_at->diffForHumans().'.',
                'level' => 'warning',
            ];
        }

        return $alerts;
    }

    /**
     * @return Collection<int, User>
     */
    private function recipients(): Collection
    {
        return User::query()
            ->whereIn('role', [User::ROLE_PLATFORM_OWNER, User::ROLE_PLATFORM_ADMIN, User::ROLE_ADMIN])
            ->where(function (Builder $query): void {
                $query
                    ->where('is_suspended', false)
                    ->orWhereNull('is_suspended');
            })
            ->orderBy('id')
            ->get();
    }

    /**
     * @param Collection<int, User> $recipients
     */
    private function send(Collection $recipients, string $title, string $body, string $level): void
    {
        foreach ($recipients as $recipient) {
            $notification = Notification::make()
                ->title($title)
                ->body($body)
                ->actions([$this->viewAccountAccessAction()]);

            match ($level) {
                'danger' => $notification->danger(),
                'warning' => $notification->warning(),
                default => $notification->info(),
            };

            $notification->sendToDatabase($recipient, isEventDispatched: true);
        }
    }

    private function viewAccountAccessAction(): Action
    {
        return Action::make('viewAccountAccess')
            ->label('Open account access')
            ->button()
            ->markAsRead()
            ->url(PlatformUserResource::getUrl(panel: 'platform'));
    }
}
