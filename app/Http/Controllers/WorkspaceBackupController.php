<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Services\WorkspaceBackupService;
use Illuminate\Support\Str;

class WorkspaceBackupController extends Controller
{
    public function download(Workspace $workspace, WorkspaceBackupService $backups)
    {
        abort_unless($workspace->canManageMembersBy(auth()->user()), 403);
        $path = $backups->create($workspace);
        $filename = 'mobilitycloud-'.Str::slug($workspace->name).'-'.now()->format('Y-m-d-His').'.zip';

        return response()->download($path, $filename, ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }
}
