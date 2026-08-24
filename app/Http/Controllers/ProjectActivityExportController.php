<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Support\Str;

class ProjectActivityExportController extends Controller
{
    public function __invoke(Project $project)
    {
        abort_unless(auth()->check() && $project->canAccessProjectModule(auth()->user(), 'edit'), 403);

        $filename = 'project-activity-'.Str::slug($project->acronym ?: $project->name).'.csv';

        return response()->streamDownload(function () use ($project): void {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, ['Date and time', 'User', 'Action', 'Description', 'Subject', 'Details']);

            $project->activityLogs()->with('user')->orderBy('created_at')->chunkById(250, function ($entries) use ($stream): void {
                foreach ($entries as $entry) {
                    fputcsv($stream, [
                        $entry->created_at?->format('Y-m-d H:i:s'),
                        $entry->user?->name ?: 'System',
                        $entry->event,
                        $entry->description,
                        $entry->subject_type ? class_basename($entry->subject_type).' #'.$entry->subject_id : '',
                        json_encode($entry->metadata ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]);
                }
            });

            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
