<?php

namespace App\Services;

use App\Models\BudgetTransfer;
use App\Models\SavedCalculation;
use App\Models\Workspace;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class WorkspaceBackupService
{
    public function create(Workspace $workspace): string
    {
        $path = tempnam(sys_get_temp_dir(), 'mobilitycloud-backup-');
        if ($path === false) {
            throw new RuntimeException('Could not create the backup file.');
        }

        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not open the backup archive.');
        }

        $projects = $workspace->projects()->withTrashed()->with([
            'members:id,name,email',
            'applicationSections',
            'budgetLines' => fn ($query) => $query->orderBy('sort_order'),
            'budgetLines.expenses' => fn ($query) => $query->withTrashed(),
            'participants.attachments',
            'documents',
            'tasks',
            'activityLogs',
        ])->get();

        $payload = [
            'exported_at' => now()->toIso8601String(),
            'format_version' => 1,
            'workspace' => $workspace->toArray(),
            'team' => $workspace->users()->orderBy('name')->get()->map(fn ($user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->pivot->role,
                'joined_at' => $user->pivot->joined_at,
            ])->all(),
            'content_library' => $workspace->contentBlocks()->get()->toArray(),
            'saved_calculations' => SavedCalculation::query()->where('workspace_id', $workspace->id)->get()->toArray(),
            'projects' => $projects->map(fn ($project): array => [
                'project' => $project->attributesToArray(),
                'member_ids' => $project->members->pluck('id')->all(),
                'application_sections' => $project->applicationSections->toArray(),
                'budget_lines' => $project->budgetLines->map(fn ($line): array => [
                    ...$line->attributesToArray(),
                    'expenses' => $line->expenses->map->attributesToArray()->all(),
                ])->all(),
                'budget_transfers' => BudgetTransfer::query()->where('project_id', $project->id)->get()->toArray(),
                'participants' => $project->participants->map(fn ($participant): array => [
                    ...$participant->attributesToArray(),
                    'attachments' => $participant->attachments->map->attributesToArray()->all(),
                ])->all(),
                'documents' => $project->documents->map->attributesToArray()->all(),
                'tasks' => $project->tasks->map->attributesToArray()->all(),
                'activity_log' => $project->activityLogs->map->attributesToArray()->all(),
            ])->all(),
        ];

        $zip->addFromString('workspace-data.json', json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR,
        ));
        $zip->addFromString('README.txt', "MobilityCloud workspace backup\n\nThe workspace-data.json file contains the structured records.\nThe files directory contains uploaded evidence and documents, grouped by project.\nGenerated PDFs can be regenerated from the structured data after restoration.\n");

        foreach ($projects as $project) {
            $projectDir = 'files/'.$project->id.'-'.$this->safeName($project->name);

            foreach ($project->participants as $participant) {
                foreach ($participant->attachments as $attachment) {
                    $this->addStoredFile($zip, $attachment->disk, $attachment->path, $projectDir.'/participants/'.$participant->id.'-'.$this->safeName($participant->fullName()).'/'.$attachment->id.'-'.$this->safeFilename($attachment->original_name));
                }
            }

            foreach ($project->budgetLines->flatMap->expenses as $expense) {
                $this->addStoredFile($zip, $expense->attachment_disk, $expense->attachment_path, $projectDir.'/expenses/'.$expense->id.'-'.$this->safeFilename($expense->attachment_name));
                foreach (['agreement', 'acceptance', 'payment'] as $kind) {
                    $copy = $expense->conventionSignedCopy($kind);
                    $this->addStoredFile($zip, $copy['disk'], $copy['path'], $projectDir.'/civil-conventions/'.$expense->id.'-'.$kind.'-'.$this->safeFilename($copy['name']));
                }
            }

            foreach ($project->documents as $document) {
                $this->addStoredFile($zip, $document->file_disk, $document->file_path, $projectDir.'/documents/'.$document->id.'-original-'.$this->safeFilename($document->file_name));
                $this->addStoredFile($zip, $document->signed_disk, $document->signed_path, $projectDir.'/documents/'.$document->id.'-signed-'.$this->safeFilename($document->signed_name));
            }
        }

        $zip->close();

        return $path;
    }

    private function addStoredFile(ZipArchive $zip, ?string $disk, ?string $path, string $archivePath): void
    {
        if (! filled($path)) {
            return;
        }

        $storage = Storage::disk($disk ?: 'local');
        if (! $storage->exists($path)) {
            return;
        }

        $stream = $storage->readStream($path);
        if ($stream === null) {
            return;
        }

        $contents = stream_get_contents($stream);
        fclose($stream);
        if ($contents !== false) {
            $zip->addFromString($archivePath, $contents);
        }
    }

    private function safeName(?string $value): string
    {
        return Str::slug($value ?: 'record') ?: 'record';
    }

    private function safeFilename(?string $value): string
    {
        $name = basename((string) ($value ?: 'file'));

        return preg_replace('/[^A-Za-z0-9._-]+/', '-', $name) ?: 'file';
    }
}
