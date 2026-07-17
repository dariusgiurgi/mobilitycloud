<?php

namespace App\Services;

use App\Models\BudgetTransfer;
use App\Models\Project;
use App\Models\ProjectDocument;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class ProjectFinalArchiveService
{
    public function create(Project $project): string
    {
        $project->loadMissing([
            'ownerAccount:id,name,email,billing_name,billing_vat,billing_country,billing_address',
            'members:id,name,email',
            'applicationSections' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            'budgetLines' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            'budgetLines.expenses' => fn ($query) => $query->withTrashed()->orderBy('expense_date')->orderBy('id'),
            'participants' => fn ($query) => $query->orderBy('last_name')->orderBy('first_name'),
            'participants.attachments',
            'documents' => fn ($query) => $query
                ->orderBy('category')
                ->orderByRaw('document_date is null')
                ->orderBy('document_date')
                ->orderBy('title')
                ->orderBy('id'),
            'tasks' => fn ($query) => $query->orderByRaw('due_date is null')->orderBy('due_date')->orderBy('priority')->orderBy('title'),
            'activityLogs' => fn ($query) => $query->orderBy('created_at')->orderBy('id'),
        ]);

        $path = tempnam(sys_get_temp_dir(), 'mobilitycloud-project-archive-');
        if ($path === false) {
            throw new RuntimeException('Could not create the final archive file.');
        }

        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not open the final archive.');
        }

        $fileIndex = [];
        $projectDir = $this->safeName($project->name);

        $payload = [
            'exported_at' => now()->toIso8601String(),
            'format_version' => 1,
            'project' => $project->attributesToArray(),
            'owner' => $project->owner()?->only(['id', 'name', 'email', 'billing_name', 'billing_vat', 'billing_country', 'billing_address']),
            'members' => $project->members->map(fn ($user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])->values()->all(),
            'application_sections' => $project->applicationSections->toArray(),
            'budget_lines' => $project->budgetLines->map(fn ($line): array => [
                ...$line->attributesToArray(),
                'expenses' => $line->expenses->map->attributesToArray()->all(),
            ])->values()->all(),
            'budget_transfers' => BudgetTransfer::query()->where('project_id', $project->id)->orderBy('created_at')->orderBy('id')->get()->toArray(),
            'participants' => $project->participants->map(fn ($participant): array => [
                ...$participant->attributesToArray(),
                'attachments' => $participant->attachments->sortBy('type')->values()->map->attributesToArray()->all(),
            ])->values()->all(),
            'documents' => $project->documents->map->attributesToArray()->all(),
            'tasks' => $project->tasks->map->attributesToArray()->all(),
            'activity_log' => $project->activityLogs->map->attributesToArray()->all(),
        ];

        foreach ($project->participants as $participant) {
            foreach ($participant->attachments->sortBy('type') as $attachment) {
                $this->addStoredFile(
                    $zip,
                    $fileIndex,
                    'participant_attachment',
                    $attachment->id,
                    'file',
                    $attachment->disk,
                    $attachment->path,
                    $projectDir.'/03-participants/'.$this->safeName($participant->fullName()).'/'.$attachment->id.'-'.$this->safeFilename($attachment->original_name),
                    $attachment->original_name,
                );
            }
        }

        foreach ($project->budgetLines as $line) {
            foreach ($line->expenses as $expense) {
                $this->addStoredFile(
                    $zip,
                    $fileIndex,
                    'expense',
                    $expense->id,
                    'evidence',
                    $expense->attachment_disk,
                    $expense->attachment_path,
                    $projectDir.'/04-budget-expenses/'.$this->safeName($line->title).'/'.$expense->id.'-'.$this->safeFilename($expense->attachment_name),
                    $expense->attachment_name,
                );

                foreach (['agreement', 'payment'] as $kind) {
                    $copy = $expense->conventionSignedCopy($kind);
                    $this->addStoredFile(
                        $zip,
                        $fileIndex,
                        'expense',
                        $expense->id,
                        $kind,
                        $copy['disk'],
                        $copy['path'],
                        $projectDir.'/05-civil-conventions/'.$expense->id.'-'.$kind.'-'.$this->safeFilename($copy['name']),
                        $copy['name'],
                    );
                }
            }
        }

        foreach ($project->documents as $document) {
            $folder = $this->documentFolder($document);
            $base = $projectDir.'/'.$folder.'/'.$document->id.'-'.$this->safeName($document->title);

            $this->addStoredFile(
                $zip,
                $fileIndex,
                'project_document',
                $document->id,
                'original',
                $document->file_disk,
                $document->file_path,
                $base.'/original-'.$this->safeFilename($document->file_name),
                $document->file_name,
            );

            $this->addStoredFile(
                $zip,
                $fileIndex,
                'project_document',
                $document->id,
                'signed',
                $document->signed_disk,
                $document->signed_path,
                $base.'/signed-'.$this->safeFilename($document->signed_name),
                $document->signed_name,
            );
        }

        $payload['file_index'] = $fileIndex;
        $zip->addFromString($projectDir.'/00-project-data/project-data.json', json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR,
        ));
        $zip->addFromString($projectDir.'/README.txt', "MobilityCloud final project archive\n\nFolders are ordered by project lifecycle: project data, application, participants, budget, civil conventions, mobility, dissemination and other project documents.\nThe project-data.json file contains the structured records and file index.\n");

        $zip->close();

        return $path;
    }

    private function documentFolder(ProjectDocument $document): string
    {
        if ($document->type === ProjectDocument::TYPE_ATTENDANCE) {
            return '06-generated-records/attendance';
        }

        if ($document->type === ProjectDocument::TYPE_EXPENSE_REPORT) {
            return '06-generated-records/expense-reports';
        }

        if (array_key_exists((string) $document->category, ProjectDocument::MOBILITY_CATEGORIES)) {
            return '07-mobility/'.$this->safeName($document->categoryLabel());
        }

        if ($document->category === 'dissemination_evidence') {
            return '08-dissemination/'.$this->safeName((string) data_get($document->metadata, 'organisation_name', 'organisation'));
        }

        return '09-project-documents/'.$this->safeName($document->categoryLabel());
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

        if ($contents === false) {
            return;
        }

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
