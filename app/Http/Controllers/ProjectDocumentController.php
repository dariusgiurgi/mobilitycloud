<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Project;
use App\Models\ProjectDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectDocumentController extends Controller
{
    public function attendance(Project $project, ProjectDocument $document)
    {
        $this->authorizeDocument($project, $document);
        abort_unless($document->type === ProjectDocument::TYPE_ATTENDANCE, 404);

        $participants = $project->participants()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $groups = $participants
            ->groupBy(fn ($participant) => trim((string) $participant->partner_organisation) ?: 'Unassigned')
            ->sortKeys(SORT_NATURAL | SORT_FLAG_CASE);

        $filename = 'attendance_'.Str::slug($project->acronym ?: $project->name)
            .'_'.$document->activity_date?->format('Y-m-d').'.pdf';

        return Pdf::loadView('pdf.attendance-list', [
            'project' => $project,
            'document' => $document,
            'groups' => $groups,
        ])->setPaper('a4', 'landscape')->download($filename);
    }

    public function signed(Project $project, ProjectDocument $document)
    {
        $this->authorizeDocument($project, $document);
        abort_unless($document->hasSignedCopy(), 404);

        return Storage::disk($document->signed_disk ?: 'local')->download(
            $document->signed_path,
            $document->signed_name ?: basename($document->signed_path)
        );
    }

    public function file(Project $project, ProjectDocument $document)
    {
        $this->authorizeDocument($project, $document);
        abort_unless($document->type === ProjectDocument::TYPE_UPLOAD && $document->hasFile(), 404);

        return Storage::disk($document->file_disk ?: 'local')->download(
            $document->file_path,
            $document->file_name ?: basename($document->file_path)
        );
    }

    public function civilConvention(Project $project, Expense $expense)
    {
        abort_unless(
            $expense->is_civil_convention
            && $expense->budgetLine()->where('project_id', $project->id)->exists(),
            404
        );
        abort_unless(auth()->user()->workspaces()->whereKey($project->workspace_id)->exists(), 403);
        abort_unless($expense->hasCompleteConventionData(), 422);

        $expense->load('budgetLine');
        $project->load('workspace');
        $data = $this->conventionData($project, $expense);
        $gross = (float) ($data['gross_amount'] ?? 0);
        $taxRate = (float) ($project->withholding_tax_rate ?? 0);
        $taxAmount = round($gross * $taxRate / 100, 2);
        $netAmount = round($gross - $taxAmount, 2);
        $type = $data['agreement_type'] ?? 'service_agreement';
        $providerName = $data['provider_name'] ?? $expense->description ?? 'provider';
        $filename = ($type === 'copyright_assignment' ? 'copyright-assignment-' : 'service-agreement-')
            .Str::slug($providerName).'.pdf';

        return Pdf::loadView('pdf.civil-convention', [
            'project' => $project,
            'expense' => $expense,
            'data' => $data,
            'gross' => $gross,
            'taxRate' => $taxRate,
            'taxAmount' => $taxAmount,
            'netAmount' => $netAmount,
            'type' => $type,
        ])->setPaper('a4', 'portrait')->download($filename);
    }

    public function acceptanceCertificate(Project $project, Expense $expense)
    {
        $this->authorizeConventionExpense($project, $expense);
        abort_unless($expense->hasCompleteConventionData() && $expense->hasCompleteAcceptanceData(), 422);

        $project->load('workspace');
        $data = $this->conventionData($project, $expense);
        $filename = 'service-acceptance-'.Str::slug($data['provider_name'] ?? 'provider').'.pdf';

        return Pdf::loadView('pdf.service-acceptance-certificate', [
            'project' => $project,
            'expense' => $expense,
            'data' => $data,
            'statusLabel' => Expense::ACCEPTANCE_STATUSES[$data['acceptance_status']] ?? 'Accepted',
        ])->setPaper('a4', 'portrait')->download($filename);
    }

    private function authorizeConventionExpense(Project $project, Expense $expense): void
    {
        abort_unless(
            $expense->is_civil_convention
            && $expense->budgetLine()->where('project_id', $project->id)->exists(),
            404
        );
        abort_unless(auth()->user()->workspaces()->whereKey($project->workspace_id)->exists(), 403);
    }

    private function conventionData(Project $project, Expense $expense): array
    {
        return array_merge([
            'agreement_type' => 'service_agreement',
            'contract_place' => null,
            'beneficiary_name' => $project->workspace->billing_name ?: $project->workspace->name,
            'beneficiary_vat' => $project->workspace->billing_vat,
            'beneficiary_address' => $project->workspace->billing_address,
            'beneficiary_representative' => null,
            'beneficiary_representative_role' => null,
            'provider_nationality' => null,
            'provider_id_type' => 'identity document',
            'provider_personal_number' => null,
            'provider_bank_name' => null,
            'provider_iban' => null,
            'service_location' => null,
            'payment_due_days' => 10,
            'rights_exclusive' => true,
            'right_to_sublicense' => true,
            'acceptance_place' => null,
            'acceptance_notes' => null,
        ], $expense->convention_data ?? []);
    }

    private function authorizeDocument(Project $project, ProjectDocument $document): void
    {
        abort_unless($document->project_id === $project->id, 404);
        abort_unless(auth()->user()->workspaces()->whereKey($project->workspace_id)->exists(), 403);
    }
}
