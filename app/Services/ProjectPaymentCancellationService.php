<?php

namespace App\Services;

use App\Models\Project;
use App\Support\PlatformAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProjectPaymentCancellationService
{
    public function refund(Project $project, string $reason, string $refundDate, string $reference): Project
    {
        abort_unless(auth()->user()?->isPlatformAdmin(), 403);

        $reason = trim($reason);
        $reference = trim($reference);
        Validator::make(['reason' => $reason, 'reference' => $reference, 'refund_date' => $refundDate], [
            'reason' => ['required', 'string', 'max:2000'],
            'reference' => ['required', 'string', 'max:255'],
            'refund_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
        ])->validate();

        return DB::transaction(function () use ($project, $reason, $refundDate, $reference): Project {
            $project = Project::query()->lockForUpdate()->findOrFail($project->getKey());

            if ($project->invoice_status !== Project::INVOICE_PAID
                || (float) $project->activation_fee_amount <= 0) {
                throw ValidationException::withMessages(['reason' => 'Only a confirmed, paid invoice can be fully refunded. Refresh the payment queue.']);
            }

            if ($project->payment_confirmed_at) {
                Validator::make(['refund_date' => $refundDate], [
                    'refund_date' => ['after_or_equal:'.$project->payment_confirmed_at->toDateString()],
                ])->validate();
            }

            $project->forceFill([
                'invoice_status' => Project::INVOICE_REFUNDED,
                'invoice_cancelled_at' => now(),
                'invoice_cancelled_by' => auth()->id(),
                'invoice_cancellation_reason' => $reason,
                'invoice_refunded_at' => $refundDate,
                'invoice_refund_amount' => $project->activation_fee_amount,
                'invoice_refund_reference' => $reference,
                'status' => $project->status === 'payment_overdue' ? 'approved' : $project->status,
            ])->save();

            PlatformAudit::log('project.invoice_refunded', 'Recorded full project payment refund for '.$project->name, $project, [
                'invoice_number' => $project->invoice_number,
                'refund_amount' => (float) $project->invoice_refund_amount,
                'refund_date' => $refundDate,
                'refund_reference' => $reference,
                'payment_confirmed_at' => $project->payment_confirmed_at?->toISOString(),
                'reason' => $reason,
            ]);

            return $project;
        });
    }

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
