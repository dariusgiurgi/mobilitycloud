<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Services\AccountWorkspaceService;
use App\Services\WorkspaceBackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WorkspaceBackupController extends Controller
{
    public function account(Request $request, AccountWorkspaceService $workspaces, WorkspaceBackupService $backups)
    {
        $workspace = $workspaces->ensureFor($request->user());

        $path = $backups->createForAccount($request->user(), $workspace);
        $filename = 'mobilitycloud-account-backup-'.Str::slug($request->user()->email).'-'.now()->format('Y-m-d-His').'.zip';

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    public function download(Workspace $workspace, WorkspaceBackupService $backups)
    {
        abort_unless($workspace->canManageMembersBy(auth()->user()), 403);
        $path = $backups->create($workspace);
        $filename = 'mobilitycloud-'.Str::slug($workspace->name).'-'.now()->format('Y-m-d-His').'.zip';

        return response()->download($path, $filename, ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }
}
