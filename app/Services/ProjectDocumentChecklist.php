<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectDocument;
use Illuminate\Support\Str;

class ProjectDocumentChecklist
{
    public function build(Project $project): array
    {
        $documents = $project->documents()->latest('id')->get();
        $uploads = $documents
            ->where('type', ProjectDocument::TYPE_UPLOAD)
            ->filter(fn (ProjectDocument $document) => $document->hasFile());
        $attendance = $documents->where('type', ProjectDocument::TYPE_ATTENDANCE);
        $expenseReports = $documents->where('type', ProjectDocument::TYPE_EXPENSE_REPORT);
        $conventions = $project->budgetLines()
            ->with(['expenses' => fn ($query) => $query->where('is_civil_convention', true)])
            ->get()
            ->flatMap->expenses;

        $partnerCount = collect($project->partners)
            ->reject(fn (array $partner) => (bool) ($partner['is_coordinator'] ?? false))
            ->count();
        $partnerFiles = $uploads->whereIn('category', ['mandate', 'partnership_agreement'])->count();
        $partnerFileLabel = $partnerFiles === 1 ? 'partner file' : 'partner files';
        $partnerLabel = $partnerCount === 1 ? 'external partner' : 'external partners';
        $signedAttendance = $attendance->filter->hasSignedCopy()->count();
        $signedReports = $expenseReports->filter->hasSignedCopy()->count();
        $disseminationOrganisations = $this->disseminationOrganisations($project);
        $disseminationEvidence = $uploads
            ->where('category', 'dissemination_evidence')
            ->filter(fn (ProjectDocument $document): bool => filled(data_get($document->metadata, 'organisation_key')));
        $disseminationReports = collect(data_get($project->action_data ?? [], 'dissemination_reports', []))
            ->filter(fn ($report): bool => filled(trim((string) $report)));
        $disseminationEvidenceCount = collect($disseminationOrganisations)
            ->filter(fn (array $organisation): bool => $disseminationEvidence
                ->filter(fn (ProjectDocument $document): bool => data_get($document->metadata, 'organisation_key') === $organisation['key'])
                ->isNotEmpty())
            ->count();
        $disseminationReportCount = collect($disseminationOrganisations)
            ->filter(fn (array $organisation): bool => $disseminationReports->has($organisation['key']))
            ->count();
        $conventionCount = $conventions->count();
        $readyConventions = $conventions->filter->hasCompleteConventionData()->count();
        $signedAgreements = $conventions->filter(fn ($expense) => $expense->hasConventionSignedCopy('agreement'))->count();
        $readyPayments = $conventions->filter->hasCompletePaymentData()->count();
        $signedPayments = $conventions->filter(fn ($expense) => $expense->hasConventionSignedCopy('payment'))->count();

        $items = [
            $this->uploadedItem($uploads, 'grant_agreement', 'Grant agreement', 'Official grant agreement uploaded'),
            $this->uploadedItem($uploads, 'approved_application', 'Approved application', 'Final approved application uploaded'),
            $partnerCount === 0
                ? $this->item('Partner documents', 'optional', 'No partner organisations configured')
                : $this->item(
                    'Partner documents',
                    $partnerFiles >= $partnerCount ? 'complete' : ($partnerFiles > 0 ? 'attention' : 'missing'),
                    $partnerFiles.' '.$partnerFileLabel.' for '.$partnerCount.' '.$partnerLabel,
                    'upload',
                    'mandate'
                ),
            $this->uploadedItem($uploads, 'activity_agenda', 'Activity agenda', 'Current activity agenda uploaded'),
            count($disseminationOrganisations) === 0
                ? $this->item('Dissemination evidence', 'optional', 'No organisations configured for dissemination')
                : $this->item(
                    'Dissemination evidence',
                    $disseminationEvidenceCount >= count($disseminationOrganisations) && $disseminationReportCount >= count($disseminationOrganisations)
                        ? 'complete'
                        : ($disseminationEvidenceCount > 0 || $disseminationReportCount > 0 ? 'attention' : 'missing'),
                    $disseminationEvidenceCount.'/'.count($disseminationOrganisations).' with evidence, '
                    .$disseminationReportCount.'/'.count($disseminationOrganisations).' with report',
                    'open_dissemination'
                ),
            $attendance->isEmpty()
                ? $this->item('Attendance records', 'missing', 'No attendance list generated', 'generate_attendance')
                : $this->item(
                    'Attendance records',
                    $signedAttendance === $attendance->count() ? 'complete' : 'attention',
                    $signedAttendance.'/'.$attendance->count().' signed',
                    $signedAttendance === $attendance->count() ? null : 'pending_signatures'
                ),
            $expenseReports->isEmpty()
                ? $this->item('Expense reports', 'missing', 'No official expense report generated', 'generate_expense_report')
                : $this->item(
                    'Expense reports',
                    $signedReports === $expenseReports->count() ? 'complete' : 'attention',
                    $signedReports.'/'.$expenseReports->count().' signed',
                    $signedReports === $expenseReports->count() ? null : 'pending_signatures'
                ),
            $conventionCount === 0
                ? $this->item('Civil conventions', 'optional', 'No civil convention expenses')
                : $this->item(
                    'Civil conventions',
                    $readyConventions === $conventionCount && $signedAgreements === $conventionCount ? 'complete' : 'attention',
                    $readyConventions.'/'.$conventionCount.' ready, '.$signedAgreements.' signed agreement(s); '
                    .$readyPayments.' payment evidence record(s), '.$signedPayments.' signed',
                    $signedAgreements === $conventionCount ? null : 'open_conventions'
                ),
        ];

        return [
            'items' => $items,
            'complete' => collect($items)->where('status', 'complete')->count(),
            'attention' => collect($items)->where('status', 'attention')->count(),
            'missing' => collect($items)->where('status', 'missing')->count(),
            'optional' => collect($items)->where('status', 'optional')->count(),
        ];
    }

    private function uploadedItem($uploads, string $category, string $label, string $completeDetail): array
    {
        $count = $uploads->where('category', $category)->count();

        return $this->item(
            $label,
            $count > 0 ? 'complete' : 'missing',
            $count > 0 ? $completeDetail : 'Not uploaded yet',
            $count > 0 ? null : 'upload',
            $category
        );
    }

    private function item(string $label, string $status, string $detail, ?string $action = null, ?string $category = null): array
    {
        return compact('label', 'status', 'detail', 'action', 'category');
    }

    private function disseminationOrganisations(Project $project): array
    {
        $partners = collect($project->partners)
            ->filter(fn (array $partner): bool => filled($partner['name'] ?? null))
            ->values();

        if ($partners->isEmpty() && $project->workspace) {
            $partners = collect([[
                'name' => $project->workspace->name,
                'country' => null,
                'oid' => null,
            ]]);
        }

        return $partners
            ->map(fn (array $partner, int $index): array => [
                'key' => $this->disseminationOrganisationKey($partner, $index),
            ])
            ->values()
            ->all();
    }

    private function disseminationOrganisationKey(array $partner, int $index): string
    {
        if (filled($partner['oid'] ?? null)) {
            return 'oid_'.Str::slug((string) $partner['oid'], '_');
        }

        return 'org_'.substr(sha1(trim(($partner['name'] ?? 'organisation').'|'.($partner['country'] ?? '').'|'.$index)), 0, 12);
    }
}
