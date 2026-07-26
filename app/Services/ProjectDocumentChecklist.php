<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectDocument;

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
}
