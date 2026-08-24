<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ParticipantAttachment;
use Illuminate\Support\Facades\Storage;

class AttachmentDownloadController extends Controller
{
    public function participant(ParticipantAttachment $attachment)
    {
        $attachment->loadMissing('participant.project');
        $project = $attachment->participant?->project;

        abort_unless(auth()->check() && $project && $project->canAccessProjectModule(auth()->user(), 'participants'), 403);

        if ($project->operationalModulesLockedUntilPayment()) {
            return response()->view('errors.project-payment-locked', [
                'project' => $project,
            ], 402);
        }

        abort_unless($attachment->exists(), 404);

        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            $attachment->original_name ?: basename($attachment->path)
        );
    }

    public function expense(Expense $expense)
    {
        $expense->loadMissing('budgetLine.project');
        $project = $expense->budgetLine?->project;

        abort_unless(auth()->check() && $project && $project->canAccessProjectModule(auth()->user(), 'budget'), 403);
        abort_unless($expense->attachmentExists(), 404);

        return Storage::disk($expense->attachment_disk)->download(
            $expense->attachment_path,
            $expense->attachment_name ?: basename($expense->attachment_path)
        );
    }
}
