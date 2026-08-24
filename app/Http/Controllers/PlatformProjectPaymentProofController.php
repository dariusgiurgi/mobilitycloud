<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Support\PlatformAudit;
use Illuminate\Support\Facades\Storage;

class PlatformProjectPaymentProofController extends Controller
{
    public function __invoke(Project $project)
    {
        abort_unless(auth()->user()?->isPlatformAdmin(), 403);

        $path = $project->approved_grant_proof_path;
        $disk = $project->approved_grant_proof_disk ?: 'local';

        abort_unless(filled($path) && Storage::disk($disk)->exists($path), 404);

        PlatformAudit::log('project.approval_proof_opened', 'Opened approval proof for '.$project->name, $project, [
            'file_name' => $project->approved_grant_proof_original_name ?: basename($path),
        ]);

        return Storage::disk($disk)->response(
            $path,
            $project->approved_grant_proof_original_name ?: basename($path),
            [],
            'inline',
        );
    }
}
