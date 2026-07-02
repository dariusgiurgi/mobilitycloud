<?php

namespace App\Services;

use App\Filament\Resources\PlatformSubscriptions\PlatformSubscriptionResource;
use App\Models\User;
use App\Models\Workspace;
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

        Workspace::query()
            ->orderBy('id')
            ->chunkById(100, function (Collection $workspaces) use ($recipients, &$sent): void {
                foreach ($workspaces as $workspace) {
                    $sent += $this->dispatchForWorkspace($workspace, $recipients);
                }
            });

        return $sent;
    }

    /**
     * @param Collection<int, User> $recipients
     */
    private function dispatchForWorkspace(Workspace $workspace, Collection $recipients): int
    {
        $sent = 0;

        foreach ($this->alertsFor($workspace) as $alert) {
            if ($workspace->{$alert['timestamp']}) {
                continue;
            }

            $this->send($recipients, $workspace, $alert['title'], $alert['body'], $alert['level']);

            $workspace->forceFill([$alert['timestamp'] => now()])->saveQuietly();
            $sent++;
        }

        return $sent;
    }

    /**
     * @return array<int, array{timestamp: string, title: string, body: string, level: string}>
     */
    private function alertsFor(Workspace $workspace): array
    {
        $alerts = [];

        if ($workspace->subscription_status === 'trial' && $workspace->trial_ends_at) {
            if ($workspace->trial_ends_at->isPast()) {
                $alerts[] = [
                    'timestamp' => 'trial_expired_alerted_at',
                    'title' => 'Trial expired',
                    'body' => $workspace->name.' trial ended '.$workspace->trial_ends_at->diffForHumans().'.',
                    'level' => 'danger',
                ];
            } elseif ($workspace->trial_ends_at->between(now(), now()->addDays(7))) {
                $alerts[] = [
                    'timestamp' => 'trial_ending_alerted_at',
                    'title' => 'Trial ending soon',
                    'body' => $workspace->name.' trial ends '.$workspace->trial_ends_at->diffForHumans().'.',
                    'level' => 'warning',
                ];
            }
        }

        if ($workspace->subscription_ends_at) {
            if ($workspace->subscription_ends_at->isPast() || $workspace->subscription_status === 'expired') {
                $alerts[] = [
                    'timestamp' => 'subscription_expired_alerted_at',
                    'title' => 'Subscription expired',
                    'body' => $workspace->name.' subscription ended '.$workspace->subscription_ends_at->diffForHumans().'.',
                    'level' => 'danger',
                ];
            } elseif ($workspace->subscription_ends_at->between(now(), now()->addDays(14))) {
                $alerts[] = [
                    'timestamp' => 'subscription_ending_alerted_at',
                    'title' => 'Subscription ending soon',
                    'body' => $workspace->name.' subscription ends '.$workspace->subscription_ends_at->diffForHumans().'.',
                    'level' => 'warning',
                ];
            }
        } elseif ($workspace->subscription_status === 'expired') {
            $alerts[] = [
                'timestamp' => 'subscription_expired_alerted_at',
                'title' => 'Subscription expired',
                'body' => $workspace->name.' is marked as expired.',
                'level' => 'danger',
            ];
        }

        if ($workspace->access_override_reason && $workspace->access_override_ends_at?->between(now(), now()->addDays(7))) {
            $alerts[] = [
                'timestamp' => 'manual_access_ending_alerted_at',
                'title' => 'Manual access ending soon',
                'body' => $workspace->name.' manual access ends '.$workspace->access_override_ends_at->diffForHumans().'.',
                'level' => 'warning',
            ];
        }

        if ($this->demoResetIsStale($workspace)) {
            $alerts[] = [
                'timestamp' => 'demo_reset_stale_alerted_at',
                'title' => 'Demo reset may be stale',
                'body' => $workspace->name.' demo workspace has not been reset according to its configured frequency.',
                'level' => 'warning',
            ];
        }

        return $alerts;
    }

    private function demoResetIsStale(Workspace $workspace): bool
    {
        if (! in_array($workspace->demo_reset_frequency, ['daily', 'weekly'], true)) {
            return false;
        }

        if ($workspace->plan !== 'demo' && $workspace->subscription_status !== 'demo') {
            return false;
        }

        if (! $workspace->demo_last_reset_at) {
            return true;
        }

        return $workspace->demo_reset_frequency === 'daily'
            ? $workspace->demo_last_reset_at->lte(now()->subDay())
            : $workspace->demo_last_reset_at->lte(now()->subWeek());
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
    private function send(Collection $recipients, Workspace $workspace, string $title, string $body, string $level): void
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
