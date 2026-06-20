<?php

namespace App\Http\Controllers;

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

    private function authorizeDocument(Project $project, ProjectDocument $document): void
    {
        abort_unless($document->project_id === $project->id, 404);
        abort_unless(auth()->user()->workspaces()->whereKey($project->workspace_id)->exists(), 403);
    }
}
