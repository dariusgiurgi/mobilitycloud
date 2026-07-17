<?php

namespace App\Services;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use App\Models\User;
use App\Notifications\ProjectPaymentStatusMailNotification;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Throwable;

class ProjectPaymentNotificationService
{
    public function send(Project $project, string $status): bool
    {
        $project->loadMissing('ownerAccount');
        $owner = $project->ownerAccount;

        if (! $owner instanceof User || ! $owner->wantsNotification('billing_updates')) {
            return false;
        }

        $this->sendInApp($project, $owner, $status);
        $this->sendEmail($project, $owner, $status);

        return true;
    }

    private function sendInApp(Project $project, User $owner, string $status): void
    {
        $notification = Notification::make()
            ->title($this->title($status))
            ->body($this->body($project, $status))
            ->viewData([
                'kind' => 'project_payment_update',
                'project_id' => $project->id,
                'invoice_status' => $status,
                'sent_at' => now()->toIso8601String(),
            ])
            ->actions([
                Action::make('openProjectPaymentUpdate')
                    ->label('Open project')
                    ->button()
                    ->markAsRead()
                    ->url(ProjectResource::getUrl('overview', ['record' => $project], panel: 'admin')),
            ]);

        match ($status) {
            Project::INVOICE_PAID => $notification->success(),
            Project::INVOICE_OVERDUE => $notification->danger(),
            Project::INVOICE_SENT => $notification->warning(),
            default => $notification->info(),
        };

        $notification->sendToDatabase($owner, isEventDispatched: true);
    }

    private function sendEmail(Project $project, User $owner, string $status): void
    {
        try {
            $owner->notify(new ProjectPaymentStatusMailNotification($project, $status));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function title(string $status): string
    {
        return match ($status) {
            Project::INVOICE_SENT => 'Fiscal invoice sent',
            Project::INVOICE_PAID => 'Project payment confirmed',
            Project::INVOICE_OVERDUE => 'Project payment overdue',
            default => 'Project payment update',
        };
    }

    private function body(Project $project, string $status): string
    {
        $fee = '€ '.number_format((float) $project->activation_fee_amount, 2);
        $due = $project->invoice_due_at?->format('d M Y');

        return match ($status) {
            Project::INVOICE_SENT => $project->name.' · fiscal invoice marked as sent for '.$fee.($due ? ' · due '.$due : '.'),
            Project::INVOICE_PAID => $project->name.' · payment confirmed for '.$fee.'. Implementation access is unlocked.',
            Project::INVOICE_OVERDUE => $project->name.' · payment is overdue for '.$fee.($due ? ' · due '.$due : '.'),
            default => $project->name.' · payment status updated.',
        };
    }
}
