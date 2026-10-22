<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use RuntimeException;

class ProjectDocumentRenderer
{
    public function render(Project $project, ProjectDocument $document): \Barryvdh\DomPDF\PDF
    {
        $project->loadMissing('ownerAccount');

        if ($document->type === ProjectDocument::TYPE_ATTENDANCE) {
            $project->loadMissing(['participants' => fn ($query) => $query->orderBy('complete_name')->orderBy('last_name')]);

            return Pdf::loadView('pdf.attendance-list', [
                'project' => $project,
                'document' => $document,
                'groups' => $project->participants
                    ->groupBy(fn ($participant) => trim((string) $participant->partner_organisation) ?: 'Unassigned')
                    ->sortKeys(SORT_NATURAL | SORT_FLAG_CASE),
            ])->setPaper('a4', 'landscape');
        }

        if ($document->type === ProjectDocument::TYPE_EXPENSE_REPORT) {
            return Pdf::loadView('pdf.expense-report', [
                'project' => $project,
                'document' => $document,
                'report' => $document->metadata ?? [],
            ])->setPaper('a4', 'landscape');
        }

        throw new RuntimeException('This document does not have a generated PDF.');
    }
}
