<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Filament\Resources\PlatformProjectPayments\PlatformProjectPaymentResource;
use App\Models\Project;
use App\Support\PlatformAudit;

class ProjectPaymentOverdueService
{
    public function dispatch(): int
    {
        $updated = 0;

        PlatformProjectPaymentResource::paymentQueueQuery()
            ->whereIn('invoice_status', [Project::INVOICE_PENDING, Project::INVOICE_SENT])
            ->whereNotNull('invoice_due_at')
            ->where('invoice_due_at', '<', now())
            ->orderBy('id')
            ->chunkById(100, function ($projects) use (&$updated): void {
                foreach ($projects as $project) {
                    if ($this->markOverdue($project)) {
                        $updated++;
                    }
                }
            });

        return $updated;
    }

    private function markOverdue(Project $project): bool
    {
        $project->loadMissing('ownerAccount');

        if ($project->ownerAccount?->isUnlimitedAccount()
            || $project->invoice_status === Project::INVOICE_OVERDUE
            || $project->invoice_status === Project::INVOICE_PAID) {
            return false;
        }

        if (! $project->invoice_due_at?->isPast()) {
            return false;
        }

        $previousStatus = $project->status;
        $previousInvoiceStatus = $project->invoice_status;

        $project->forceFill([
            'invoice_status' => Project::INVOICE_OVERDUE,
            'status' => ProjectStatus::PaymentOverdue->value,
            'payment_confirmed_at' => null,
            'payment_confirmed_by' => null,
        ])->save();

        $project->refresh()->loadMissing('ownerAccount');

        app(ProjectPaymentNotificationService::class)->send($project, Project::INVOICE_OVERDUE);

        PlatformAudit::log('project.invoice_auto_overdue', 'Automatically marked project payment as overdue for '.$project->name, $project, [
            'account' => $project->ownerAccount?->email,
            'previous_project_status' => $previousStatus,
            'previous_invoice_status' => $previousInvoiceStatus,
            'invoice_due_at' => $project->invoice_due_at?->toISOString(),
            'activation_fee_amount' => (float) $project->activation_fee_amount,
        ]);

        return true;
    }
}
