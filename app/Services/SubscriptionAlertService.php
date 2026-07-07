<?php

namespace App\Services;

use App\Filament\Resources\PlatformSubscriptions\PlatformSubscriptionResource;
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

        if ($account->subscription_status === 'trial' && $account->trial_ends_at) {
            if ($account->trial_ends_at->isPast()) {
                $alerts[] = [
                    'timestamp' => 'trial_expired_alerted_at',
                    'title' => 'Trial expired',
                    'body' => $name.' trial ended '.$account->trial_ends_at->diffForHumans().'.',
                    'level' => 'danger',
                ];
            } elseif ($account->trial_ends_at->between(now(), now()->addDays(7))) {
                $alerts[] = [
                    'timestamp' => 'trial_ending_alerted_at',
                    'title' => 'Trial ending soon',
                    'body' => $name.' trial ends '.$account->trial_ends_at->diffForHumans().'.',
                    'level' => 'warning',
                ];
            }
        }

        if ($account->subscription_ends_at) {
            if ($account->subscription_ends_at->isPast() || $account->subscription_status === 'expired') {
                $alerts[] = [
                    'timestamp' => 'subscription_expired_alerted_at',
                    'title' => 'Subscription expired',
                    'body' => $name.' subscription ended '.$account->subscription_ends_at->diffForHumans().'.',
                    'level' => 'danger',
                ];
            } elseif ($account->subscription_ends_at->between(now(), now()->addDays(14))) {
                $alerts[] = [
                    'timestamp' => 'subscription_ending_alerted_at',
                    'title' => 'Subscription ending soon',
                    'body' => $name.' subscription ends '.$account->subscription_ends_at->diffForHumans().'.',
                    'level' => 'warning',
                ];
            }
        } elseif ($account->subscription_status === 'expired') {
            $alerts[] = [
                'timestamp' => 'subscription_expired_alerted_at',
                'title' => 'Subscription expired',
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

        if ($this->demoResetIsStale($account)) {
            $alerts[] = [
                'timestamp' => 'demo_reset_stale_alerted_at',
                'title' => 'Demo reset may be stale',
                'body' => $name.' demo account has not been reset according to its configured frequency.',
                'level' => 'warning',
            ];
        }

        return $alerts;
    }

    private function demoResetIsStale(User $account): bool
    {
        if (! in_array($account->demo_reset_frequency, ['daily', 'weekly'], true)) {
            return false;
        }

        if ($account->plan !== 'demo' && $account->subscription_status !== 'demo') {
            return false;
        }

        if (! $account->demo_last_reset_at) {
            return true;
        }

        return $account->demo_reset_frequency === 'daily'
            ? $account->demo_last_reset_at->lte(now()->subDay())
            : $account->demo_last_reset_at->lte(now()->subWeek());
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
                ->actions([$this->viewSubscriptionsAction()]);

            match ($level) {
                'danger' => $notification->danger(),
                'warning' => $notification->warning(),
                default => $notification->info(),
            };

            $notification->sendToDatabase($recipient, isEventDispatched: true);
        }
    }

    private function viewSubscriptionsAction(): Action
    {
        return Action::make('viewSubscriptions')
            ->label('Open subscriptions')
            ->button()
            ->markAsRead()
            ->url(PlatformSubscriptionResource::getUrl(panel: 'platform'));
    }
}
