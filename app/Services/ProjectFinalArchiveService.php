<?php

namespace App\Services;

use App\Models\MobilityFeedbackCampaign;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectFinalArchive;
use App\Support\ArchivePerformance;
use App\Support\CsvExport;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

class ProjectFinalArchiveService
{
    /** @var array<int, string> */
    private array $temporaryFiles = [];

    private int $inputBytes = 0;

    private ArchivePerformance $performance;

    public function create(Project $project, ?array $selection = null, ?ArchivePerformance $performance = null): string
    {
        $this->performance = $performance ?? new ArchivePerformance;
        $this->temporaryFiles = [];
        $this->inputBytes = 0;
        $included = $this->selectedCategories($project, $selection);
        $documentCategoriesSelected = collect([
            'generated_records',
            'project_files',
            'mobility',
            'dissemination',
        ])->contains(fn (string $category): bool => $included[$category]);

        $snapshot = $this->performance->measure('snapshot', fn () => app(ProjectArchiveSnapshot::class)->capture($project, $included));
        $project = $snapshot['project'];

        $path = tempnam($this->workDirectory(), 'mobilitycloud-project-archive-');
        if ($path === false) {
            throw new RuntimeException('Could not create the final archive file.');
        }

        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($path);

            throw new RuntimeException('Could not open the final archive.');
        }

        $zipOpen = true;

        try {
            $fileIndex = [];
            $projectDir = $this->safeName($project->name);
            $activityArchivePath = $projectDir.'/00-project-data/activity-log.csv';
            $documents = $documentCategoriesSelected
                ? $project->documents
                    ->filter(fn (ProjectDocument $document): bool => $this->shouldIncludeDocument($document, $included))
                    ->values()
                : collect();
            $feedbackCampaigns = $snapshot['campaigns'];

            $payload = $this->performance->measure('serialize', fn () => [
                'exported_at' => now()->toIso8601String(),
                'format_version' => 3,
                'snapshot_at' => $snapshot['captured_at'],
                'project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'acronym' => $project->acronym,
                    'grant_ref' => $project->grant_ref,
                    'ka_action' => $project->ka_action,
                    'status' => $project->status,
                    'start_date' => $project->start_date?->toDateString(),
                    'end_date' => $project->end_date?->toDateString(),
                    'approved_grant_amount' => $project->approved_grant_amount,
                    'approved_grant_currency' => $project->approved_grant_currency,
                    'description' => $included['project_data'] ? $project->description : null,
                    'partner_orgs' => $included['project_data'] ? $project->partner_orgs : [],
                    'notes' => $included['project_data'] ? $project->notes : null,
                ],
                'included_sections' => $included,
                'owner' => $project->owner()?->only(['id', 'name', 'email', 'billing_name', 'billing_vat', 'billing_country', 'billing_address']),
                'members' => $project->members->map(fn ($user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ])->values()->all(),
                'application_flows' => $included['application'] ? data_get($project->action_data, 'application_flows', []) : [],
                'mobilities' => ($included['mobility'] || $included['dissemination'] || $included['participants'] || $included['feedback'])
                    ? $project->mobilities->map(fn ($mobility): array => [
                        ...$mobility->only(['id', 'name', 'start_date', 'end_date', 'destination_country', 'host_organisation', 'participating_organisations']),
                        'workspace_data' => array_intersect_key($mobility->workspace_data ?? [], array_flip(array_merge(
                            $included['mobility'] ? ['report', 'photo_folder_url', 'photo_folder_links', 'final_video_url', 'evidence_days'] : [],
                            $included['dissemination'] ? ['dissemination_reports'] : [],
                        ))),
                    ])->all() : [],
                'application_sections' => $included['application'] ? $project->applicationSections->toArray() : [],
                'budget_lines' => $included['budget'] ? $project->budgetLines->map(fn ($line): array => [
                    ...$line->attributesToArray(),
                    'expenses' => $line->expenses->map->attributesToArray()->all(),
                ])->values()->all() : [],
                'budget_transfers' => $snapshot['transfers'],
                'participants' => $included['participants'] ? $project->participants->map(fn ($participant): array => [
                    ...$participant->attributesToArray(),
                    'mobilities' => $participant->mobilities->map(fn ($mobility): array => [
                        'id' => $mobility->id, 'role' => $mobility->pivot->role, 'status' => $mobility->pivot->status,
                    ])->all(),
                    'attachments' => $participant->attachments->sortBy('type')->values()->map->attributesToArray()->all(),
                ])->values()->all() : [],
                'documents' => $documents->map->attributesToArray()->all(),
                'tasks' => $included['project_data'] ? $project->tasks->map->attributesToArray()->all() : [],
                'activity_log' => [],
                'activity_log_export' => $included['project_data'] ? [
                    'format' => 'csv',
                    'path' => $activityArchivePath,
                    'records' => $project->activityLogs()->where('id', '<=', $snapshot['activity_max_id'])->count(),
                ] : null,
                'feedback_campaigns' => $feedbackCampaigns->map(fn (MobilityFeedbackCampaign $campaign): array => [
                    'title' => $campaign->title,
                    'mobility' => $campaign->mobility?->name,
                    'responses' => $campaign->responses->count(),
                    'link_status' => $campaign->hasActiveLink() ? 'open' : 'closed',
                ])->values()->all(),
            ]);

            if ($included['participants']) {
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
            }

            if ($included['budget'] || $included['agreements']) {
                foreach ($project->budgetLines as $line) {
                    foreach ($line->expenses as $expense) {
                        // Deleted expenses remain in the data history; their files may have been deliberately purged.
                        if ($expense->trashed()) {
                            continue;
                        }
                        if ($included['budget']) {
                            $this->addStoredFile(
                                $zip,
                                $fileIndex,
                                'expense',
                                $expense->id,
                                'evidence',
                                $expense->attachment_disk,
                                $expense->attachment_path,
                                $projectDir.'/04-budget-expenses/'.$this->safeName($line->title).'/'.$expense->id.'-'.$this->safeFilename($expense->supportingFileName($project)),
                                $expense->supportingFileName($project),
                            );
                        }

                        if (! $included['agreements']) {
                            continue;
                        }

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
            }

            foreach ($documents as $document) {
                $folder = $this->documentFolder($document);
                $base = $projectDir.'/'.$folder.'/'.$document->id.'-'.$this->safeName($document->title);

                if (in_array($document->type, [ProjectDocument::TYPE_ATTENDANCE, ProjectDocument::TYPE_EXPENSE_REPORT], true)) {
                    $this->addGeneratedFile($zip, $fileIndex, $base.'/generated.pdf',
                        $this->performance->measure('pdf', fn () => app(ProjectDocumentRenderer::class)->render($project, $document)->output()),
                        'project_document', $document->id);
                }

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

            if ($included['feedback']) {
                $reports = app(MobilityFeedbackReportService::class);

                foreach ($feedbackCampaigns as $campaign) {
                    $filename = $reports->filename($campaign);
                    $archivePath = $projectDir.'/10-participant-feedback/'
                        .$this->safeName($campaign->mobility?->name).'-'.$campaign->id.'/'
                        .$filename;
                    $this->addGeneratedFile($zip, $fileIndex, $archivePath,
                        $this->performance->measure('pdf', fn () => $reports->output($campaign)),
                        'mobility_feedback_campaign', $campaign->id, 'anonymous_feedback_pdf');
                }
            }

            if ($included['project_data']) {
                $this->addActivityLog($zip, $fileIndex, $project, $activityArchivePath, $snapshot['activity_max_id']);
            }

            if ($included['project_files']) {
                $this->addStoredFile($zip, $fileIndex, 'project', $project->id, 'grant_approval',
                    $project->approved_grant_proof_disk, $project->approved_grant_proof_path,
                    $projectDir.'/09-project-documents/grant-approval/'.$this->safeFilename($project->approved_grant_proof_original_name),
                    $project->approved_grant_proof_original_name);
            }
            app(ProjectArchiveReports::class)->add($project, $included,
                function (string $name, string $contents) use ($zip, &$fileIndex, $projectDir, $project): void {
                    $this->addGeneratedFile($zip, $fileIndex, $projectDir.'/'.$name, $contents, 'project', $project->id);
                }, $this->performance);
            $payload['file_index'] = $fileIndex;
            $this->addGeneratedFile($zip, $fileIndex, $projectDir.'/INDEX.html',
                $this->performance->measure('serialize', fn () => view('exports.archive-index', ['payload' => $payload])->render()), 'project', $project->id);
            if (! $zip->addFromString($projectDir.'/00-project-data/project-data.json', $this->performance->measure('serialize', fn () => json_encode(
                $payload,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR,
            )))) {
                throw new RuntimeException('Could not write archive metadata.');
            }
            $zip->addFromString($projectDir.'/README.txt', "MobilityCloud final project archive\n\nOpen INDEX.html for the readable project summary and document index. This archive includes only the categories selected in Finalisation. The project-data.json file contains the structured records and file index. When Project data & activity is selected, the complete activity history is stored separately as activity-log.csv so large histories can be exported safely.\n");

            $this->performance->increment('zip_entries', $zip->numFiles);
            $this->performance->measure('zip_finalize', function () use ($zip): void {
                if (! $zip->close()) {
                    throw new RuntimeException('Could not finish the final archive.');
                }
            });

            $zipOpen = false;

            return $path;
        } catch (Throwable $exception) {
            if ($zipOpen) {
                $zip->close();
            }

            @unlink($path);

            throw $exception;
        } finally {
            foreach ($this->temporaryFiles as $temporaryFile) {
                @unlink($temporaryFile);
            }

            $this->temporaryFiles = [];
        }
    }

    private function documentFolder(ProjectDocument $document): string
    {
        if ($document->type === ProjectDocument::TYPE_ATTENDANCE) {
            return '06-generated-records/attendance';
        }

        if ($document->type === ProjectDocument::TYPE_EXPENSE_REPORT) {
            return '06-generated-records/expense-reports';
        }

        if (filled(data_get($document->metadata, 'template_key'))) {
            return '06-generated-records/project-templates';
        }

        if (array_key_exists((string) $document->category, ProjectDocument::MOBILITY_CATEGORIES)) {
            return '07-mobility/'.$this->safeName($document->categoryLabel());
        }

        if ($document->category === 'dissemination_evidence') {
            return '08-dissemination/'.$this->safeName((string) data_get($document->metadata, 'organisation_name', 'organisation'));
        }

        return '09-project-documents/'.$this->safeName($document->categoryLabel());
    }

    /**
     * A final archive can be tailored without changing or deleting project data.
     * Existing projects keep the complete archive behaviour until an owner saves a choice.
     */
    private function selectedCategories(Project $project, ?array $selection = null): array
    {
        $defaults = array_fill_keys(ProjectFinalArchive::CATEGORY_KEYS, true);

        $saved = $selection ?? data_get($project->action_data, 'finalisation.include');
        if (! is_array($saved) || $saved === []) {
            return $defaults;
        }

        return collect($defaults)
            ->map(fn (bool $default, string $key): bool => (bool) ($saved[$key] ?? false))
            ->all();
    }

    private function shouldIncludeDocument(ProjectDocument $document, array $included): bool
    {
        if (in_array($document->type, [ProjectDocument::TYPE_ATTENDANCE, ProjectDocument::TYPE_EXPENSE_REPORT], true)
            || filled(data_get($document->metadata, 'template_key'))) {
            return $included['generated_records'];
        }

        if (array_key_exists((string) $document->category, ProjectDocument::MOBILITY_CATEGORIES)) {
            return $included['mobility'];
        }

        if ($document->category === 'dissemination_evidence') {
            return $included['dissemination'];
        }

        return $included['project_files'];
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

        $this->performance->measure('file_copy', function () use ($zip, &$fileIndex, $entity, $recordId, $slot, $disk, $path, $archivePath, $originalName): void {
            $storage = Storage::disk($disk ?: 'local');
            if (! $storage->exists($path)) {
                throw new RuntimeException('A referenced archive file is missing: '.$entity.' #'.$recordId.' ('.$slot.'). Restore or remove the broken reference and prepare again.');
            }

            $size = $storage->size($path);
            $free = disk_free_space($this->workDirectory());
            if ($free !== false && $free < $this->inputBytes + ($size * 2) + 104857600) {
                throw new RuntimeException('Insufficient temporary disk space to safely prepare the archive.');
            }
            $this->inputBytes += $size;
            $stream = $storage->readStream($path);
            if (! is_resource($stream)) {
                throw new RuntimeException('Could not read a file selected for the final archive.');
            }

            $temporaryFile = $this->temporaryFile('mobilitycloud-archive-file-');
            $output = fopen($temporaryFile, 'w+b');
            if (! is_resource($output)) {
                fclose($stream);

                throw new RuntimeException('Could not prepare a file for the final archive.');
            }

            try {
                if (stream_copy_to_stream($stream, $output) !== $size) {
                    throw new RuntimeException('Could not stream a file into the final archive.');
                }
            } finally {
                fclose($stream);
                fclose($output);
            }

            if (! $zip->addFile($temporaryFile, $archivePath)) {
                throw new RuntimeException('Could not add a selected file to the final archive.');
            }

            $fileIndex[] = [
                'entity' => $entity,
                'record_id' => $recordId,
                'slot' => $slot,
                'archive_path' => $archivePath,
                'original_name' => $originalName,
                'size' => filesize($temporaryFile) ?: 0,
                'sha256' => hash_file('sha256', $temporaryFile),
            ];
            $this->performance->increment('copied_files');
            $this->performance->increment('copied_bytes', $size);
        });
    }

    private function addActivityLog(ZipArchive $zip, array &$fileIndex, Project $project, string $archivePath, int $maximumId): void
    {
        $this->performance->measure('activity_csv', function () use ($zip, &$fileIndex, $project, $archivePath, $maximumId): void {
            $temporaryFile = $this->temporaryFile('mobilitycloud-activity-log-');
            $output = fopen($temporaryFile, 'w+b');
            if (! is_resource($output)) {
                throw new RuntimeException('Could not prepare the activity history for the final archive.');
            }

            try {
                fwrite($output, "\xEF\xBB\xBF");
                CsvExport::writeRow($output, [
                    'Date', 'User ID', 'Event', 'Subject type', 'Subject ID', 'Description', 'Metadata',
                ], ';');

                $project->activityLogs()
                    ->where('id', '<=', $maximumId)
                    ->orderBy('id')
                    ->chunkById(500, function ($entries) use ($output): void {
                        foreach ($entries as $entry) {
                            CsvExport::writeRow($output, [
                                $entry->created_at?->toIso8601String(),
                                $entry->user_id,
                                $entry->event,
                                $entry->subject_type,
                                $entry->subject_id,
                                $entry->description,
                                json_encode($entry->metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE),
                            ], ';');
                            $this->performance->increment('activity_rows');
                        }
                    });
            } finally {
                fclose($output);
            }

            if (! $zip->addFile($temporaryFile, $archivePath)) {
                throw new RuntimeException('Could not add the activity history to the final archive.');
            }

            $fileIndex[] = [
                'entity' => 'project_activity_log',
                'record_id' => $project->id,
                'slot' => 'complete_csv',
                'archive_path' => $archivePath,
                'original_name' => 'activity-log.csv',
                'size' => filesize($temporaryFile) ?: 0,
                'sha256' => hash_file('sha256', $temporaryFile),
            ];
            $this->performance->increment('generated_files');
            $this->performance->increment('generated_bytes', filesize($temporaryFile) ?: 0);
        });
    }

    private function addGeneratedFile(ZipArchive $zip, array &$index, string $path, string $contents, string $entity, int $recordId, string $slot = 'generated'): void
    {
        $this->performance->measure('generated_write', function () use ($zip, &$index, $path, $contents, $entity, $recordId, $slot): void {
            $temporary = $this->temporaryFile('mobilitycloud-archive-report-');
            if (file_put_contents($temporary, $contents) === false || ! $zip->addFile($temporary, $path)) {
                throw new RuntimeException('Could not add a generated archive document.');
            }
            $index[] = ['entity' => $entity, 'record_id' => $recordId, 'slot' => $slot,
                'archive_path' => $path, 'original_name' => basename($path),
                'size' => strlen($contents), 'sha256' => hash('sha256', $contents)];
            $this->performance->increment('generated_files');
            $this->performance->increment('generated_bytes', strlen($contents));
        });
    }

    private function temporaryFile(string $prefix): string
    {
        $temporaryFile = tempnam($this->workDirectory(), $prefix);
        if ($temporaryFile === false) {
            throw new RuntimeException('Could not prepare a temporary file for the final archive.');
        }

        $this->temporaryFiles[] = $temporaryFile;

        return $temporaryFile;
    }

    private function workDirectory(): string
    {
        $directory = storage_path('app/private/final-archive-work');
        File::ensureDirectoryExists($directory, 0700);

        return $directory;
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
