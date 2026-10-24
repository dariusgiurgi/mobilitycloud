<?php

namespace App\Services;

use App\Models\Project;
use App\Support\PlatformAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProjectPaymentCancellationService
{
    public function cancel(Project $project, string $reason): Project
    {
        abort_unless(auth()->user()?->isPlatformAdmin(), 403);

        $reason = trim($reason);
        Validator::make(['reason' => $reason], ['reason' => ['required', 'string', 'max:2000']])->validate();

        return DB::transaction(function () use ($project, $reason): Project {
            $project = Project::query()->lockForUpdate()->findOrFail($project->getKey());

            if ($project->payment_confirmed_at
                || ! in_array($project->invoice_status, [Project::INVOICE_PENDING, Project::INVOICE_SENT, Project::INVOICE_OVERDUE], true)) {
                throw ValidationException::withMessages(['reason' => 'Only unpaid, active invoices / orders can be cancelled. Refresh the payment queue.']);
            }

            $previousStatus = $project->invoice_status;
            $project->forceFill([
                'invoice_status' => Project::INVOICE_CANCELLED,
                'invoice_cancelled_at' => now(),
                'invoice_cancelled_by' => auth()->id(),
                'invoice_cancellation_reason' => $reason,
                'status' => $project->status === 'payment_overdue' ? 'approved' : $project->status,
            ])->save();

            PlatformAudit::log('project.invoice_cancelled', 'Cancelled project payment for '.$project->name, $project, [
                'previous_invoice_status' => $previousStatus,
                'invoice_number' => $project->invoice_number,
                'activation_fee_amount' => (float) $project->activation_fee_amount,
                'reason' => $reason,
            ]);

            return $project;
        });
    }
}
