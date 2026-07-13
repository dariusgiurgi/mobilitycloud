<?php

namespace App\Services;

use App\Models\BudgetTransfer;
use App\Models\ContentBlock;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\SavedCalculation;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class WorkspaceBackupService
{
    public function create(Workspace $workspace): string
    {
        $projects = $this->projectQuery($workspace->projects())->get();

        return $this->archive($workspace, $projects, [
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
        ]);
    }

    public function createForAccount(User $user, Workspace $workspace): string
    {
        $projects = $this->projectQuery(Project::query()->visibleToAccount($user))->get();

        return $this->archive($workspace, $projects, [
            'workspace' => [
                ...$workspace->toArray(),
                'name' => 'Account backup - '.$user->email,
                'account_id' => $user->id,
                'account_email' => $user->email,
                'document_settings' => $user->document_settings ?? [],
                'document_logo_path' => data_get($user->document_settings, 'logo_path'),
            ],
            'team' => [[
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'owner',
                'joined_at' => $user->created_at?->toDateTimeString(),
            ]],
            'content_library' => ContentBlock::query()->where('owner_id', $user->id)->get()->toArray(),
            'saved_calculations' => SavedCalculation::query()->where('created_by', $user->id)->get()->toArray(),
        ]);
    }

    private function archive(Workspace $workspace, Collection $projects, array $metadata): string
    {
        $path = tempnam(sys_get_temp_dir(), 'mobilitycloud-backup-');
        if ($path === false) {
            throw new RuntimeException('Could not create the backup file.');
        }

        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not open the backup archive.');
        }

        $payload = [
            'exported_at' => now()->toIso8601String(),
            'format_version' => 2,
            ...$metadata,
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

        $fileIndex = [];

        foreach ($projects as $project) {
            $projectDir = 'files/'.$project->id.'-'.$this->safeName($project->name);

            foreach ($project->participants as $participant) {
                foreach ($participant->attachments as $attachment) {
                    $this->addStoredFile($zip, $fileIndex, 'participant_attachment', $attachment->id, 'file', $attachment->disk, $attachment->path, $projectDir.'/participants/'.$participant->id.'-'.$this->safeName($participant->fullName()).'/'.$attachment->id.'-'.$this->safeFilename($attachment->original_name), $attachment->original_name);
                }
            }

            foreach ($project->budgetLines->flatMap->expenses as $expense) {
                $this->addStoredFile($zip, $fileIndex, 'expense', $expense->id, 'evidence', $expense->attachment_disk, $expense->attachment_path, $projectDir.'/expenses/'.$expense->id.'-'.$this->safeFilename($expense->attachment_name), $expense->attachment_name);
                foreach (['agreement', 'acceptance', 'payment'] as $kind) {
                    $copy = $expense->conventionSignedCopy($kind);
                    $this->addStoredFile($zip, $fileIndex, 'expense', $expense->id, $kind, $copy['disk'], $copy['path'], $projectDir.'/civil-conventions/'.$expense->id.'-'.$kind.'-'.$this->safeFilename($copy['name']), $copy['name']);
                }
            }

            foreach ($project->documents as $document) {
                $folder = $this->documentFolder($document);
                $base = $projectDir.'/'.$folder.'/'.$document->id.'-'.$this->safeName($document->title);
                $this->addStoredFile($zip, $fileIndex, 'project_document', $document->id, 'original', $document->file_disk, $document->file_path, $base.'/original-'.$this->safeFilename($document->file_name), $document->file_name);
                $this->addStoredFile($zip, $fileIndex, 'project_document', $document->id, 'signed', $document->signed_disk, $document->signed_path, $base.'/signed-'.$this->safeFilename($document->signed_name), $document->signed_name);
            }
        }

        $documentLogoPath = data_get($payload, 'workspace.document_logo_path')
            ?: data_get($payload, 'workspace.document_settings.logo_path');

        $this->addStoredFile(
            $zip,
            $fileIndex,
            'workspace',
            $workspace->id,
            'document_logo',
            'local',
            $documentLogoPath,
            'files/workspace/document-logo-'.$this->safeFilename($documentLogoPath),
            $documentLogoPath ? basename($documentLogoPath) : null,
        );

        $payload['file_index'] = $fileIndex;
        $zip->addFromString('workspace-data.json', json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR,
        ));
        $zip->addFromString('README.txt', "MobilityCloud workspace backup\n\nThe workspace-data.json file contains the structured records and a verified file index.\nThe files directory contains uploaded evidence and documents, grouped by project.\nUse Workspace settings > Data & backup to restore this archive safely.\n");

        $zip->close();

        return $path;
    }

    private function projectQuery($query)
    {
        return $query->withTrashed()->with([
            'members:id,name,email',
            'applicationSections',
            'budgetLines' => fn ($query) => $query->orderBy('sort_order'),
            'budgetLines.expenses' => fn ($query) => $query->withTrashed(),
            'participants.attachments',
            'documents' => fn ($query) => $query
                ->orderBy('category')
                ->orderByRaw('document_date is null')
                ->orderBy('document_date')
                ->orderBy('title')
                ->orderBy('id'),
            'tasks',
            'activityLogs',
        ]);
    }

    private function addStoredFile(
        ZipArchive $zip,
        array &$fileIndex,
        string $entity,
        int $recordId,
        string $slot,
        ?string $disk,
        ?string $path,
        string $archivePath,
        ?string $originalName,
    ): void {
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
            $fileIndex[] = [
                'entity' => $entity,
                'record_id' => $recordId,
                'slot' => $slot,
                'archive_path' => $archivePath,
                'original_name' => $originalName,
                'size' => strlen($contents),
                'sha256' => hash('sha256', $contents),
            ];
        }
    }

    private function safeName(?string $value): string
    {
        return Str::slug($value ?: 'record') ?: 'record';
    }

    private function documentFolder(ProjectDocument $document): string
    {
        if ($document->type === ProjectDocument::TYPE_ATTENDANCE) {
            return 'documents/01-generated-records/attendance';
        }

        if ($document->type === ProjectDocument::TYPE_EXPENSE_REPORT) {
            return 'documents/01-generated-records/expense-reports';
        }

        if (array_key_exists((string) $document->category, ProjectDocument::MOBILITY_CATEGORIES)) {
            return 'documents/02-mobility/'.$this->safeName($document->categoryLabel());
        }

        if ($document->category === 'dissemination_evidence') {
            return 'documents/03-dissemination/'.$this->safeName((string) data_get($document->metadata, 'organisation_name', 'organisation'));
        }

        return 'documents/04-project-documents/'.$this->safeName($document->categoryLabel());
    }

    private function safeFilename(?string $value): string
    {
        $name = basename((string) ($value ?: 'file'));

        return preg_replace('/[^A-Za-z0-9._-]+/', '-', $name) ?: 'file';
    }
}
