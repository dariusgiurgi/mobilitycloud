<?php

namespace App\Services;

use App\Models\BudgetTransfer;
use App\Models\MobilityFeedbackCampaign;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectFinalArchive;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

class ProjectFinalArchiveService
{
    /** @var array<int, string> */
    private array $temporaryFiles = [];

    public function create(Project $project, ?array $selection = null): string
    {
        $this->temporaryFiles = [];
        $included = $this->selectedCategories($project, $selection);
        $documentCategoriesSelected = collect([
            'generated_records',
            'project_files',
            'mobility',
            'dissemination',
        ])->contains(fn (string $category): bool => $included[$category]);

        $relations = [
            'ownerAccount:id,name,email,billing_name,billing_vat,billing_country,billing_address',
            'members:id,name,email',
        ];

        if ($included['application']) {
            $relations['applicationSections'] = fn ($query) => $query->orderBy('sort_order')->orderBy('id');
        }

        if ($included['budget'] || $included['agreements']) {
            $relations['budgetLines'] = fn ($query) => $query->orderBy('sort_order')->orderBy('id');
            $relations['budgetLines.expenses'] = fn ($query) => $query->withTrashed()->orderBy('expense_date')->orderBy('id');
        }

        if ($included['participants']) {
            $relations['participants'] = fn ($query) => $query->orderBy('complete_name')->orderBy('last_name');
            $relations[] = 'participants.attachments';
        }

        if ($documentCategoriesSelected) {
            $relations['documents'] = fn ($query) => $query
                ->orderBy('category')
                ->orderByRaw('document_date is null')
                ->orderBy('document_date')
                ->orderBy('title')
                ->orderBy('id');
        }

        if ($included['project_data']) {
            $relations['tasks'] = fn ($query) => $query->orderByRaw('due_date is null')->orderBy('due_date')->orderBy('priority')->orderBy('title');
        }

        $project->loadMissing($relations);

        $path = tempnam(sys_get_temp_dir(), 'mobilitycloud-project-archive-');
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
            $feedbackCampaigns = $included['feedback']
                ? MobilityFeedbackCampaign::query()
                    ->with(['mobility', 'responses' => fn ($query) => $query->latest('submitted_at')])
                    ->whereHas('mobility', fn ($query) => $query->where('project_id', $project->id))
                    ->orderBy('project_mobility_id')
                    ->orderBy('id')
                    ->get()
                : collect();

            $payload = [
                'exported_at' => now()->toIso8601String(),
                'format_version' => 2,
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
                ],
                'included_sections' => $included,
                'owner' => $project->owner()?->only(['id', 'name', 'email', 'billing_name', 'billing_vat', 'billing_country', 'billing_address']),
                'members' => $project->members->map(fn ($user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ])->values()->all(),
                'application_sections' => $included['application'] ? $project->applicationSections->toArray() : [],
                'budget_lines' => $included['budget'] ? $project->budgetLines->map(fn ($line): array => [
                    ...$line->attributesToArray(),
                    'expenses' => $line->expenses->map->attributesToArray()->all(),
                ])->values()->all() : [],
                'budget_transfers' => $included['budget']
                    ? BudgetTransfer::query()->where('project_id', $project->id)->orderBy('created_at')->orderBy('id')->get()->toArray()
                    : [],
                'participants' => $included['participants'] ? $project->participants->map(fn ($participant): array => [
                    ...$participant->attributesToArray(),
                    'attachments' => $participant->attachments->sortBy('type')->values()->map->attributesToArray()->all(),
                ])->values()->all() : [],
                'documents' => $documents->map->attributesToArray()->all(),
                'tasks' => $included['project_data'] ? $project->tasks->map->attributesToArray()->all() : [],
                'activity_log' => [],
                'activity_log_export' => $included['project_data'] ? [
                    'format' => 'csv',
                    'path' => $activityArchivePath,
                    'records' => $project->activityLogs()->count(),
                ] : null,
                'feedback_campaigns' => $feedbackCampaigns->map(fn (MobilityFeedbackCampaign $campaign): array => [
                    'title' => $campaign->title,
                    'mobility' => $campaign->mobility?->name,
                    'responses' => $campaign->responses->count(),
                    'link_status' => $campaign->hasActiveLink() ? 'open' : 'closed',
                ])->values()->all(),
            ];

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
                    $contents = $reports->output($campaign);
                    $temporaryReport = $this->temporaryFile('mobilitycloud-feedback-report-');

                    if (file_put_contents($temporaryReport, $contents) === false
                        || ! $zip->addFile($temporaryReport, $archivePath)) {
                        throw new RuntimeException('Could not add a feedback report to the final archive.');
                    }

                    unset($contents);
                    $fileIndex[] = [
                        'entity' => 'mobility_feedback_campaign',
                        'record_id' => $campaign->id,
                        'slot' => 'anonymous_feedback_pdf',
                        'archive_path' => $archivePath,
                        'original_name' => $filename,
                        'size' => filesize($temporaryReport) ?: 0,
                        'sha256' => hash_file('sha256', $temporaryReport),
                    ];
                }
            }

            if ($included['project_data']) {
                $this->addActivityLog($zip, $fileIndex, $project, $activityArchivePath);
            }

            $payload['file_index'] = $fileIndex;
            $zip->addFromString($projectDir.'/00-project-data/project-data.json', json_encode(
                $payload,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR,
            ));
            $zip->addFromString($projectDir.'/README.txt', "MobilityCloud final project archive\n\nThis archive includes only the categories selected in Finalisation. The project-data.json file contains the structured records and file index. When Project data & activity is selected, the complete activity history is stored separately as activity-log.csv so large histories can be exported safely.\n");

            if (! $zip->close()) {
                throw new RuntimeException('Could not finish the final archive.');
            }

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

        $storage = Storage::disk($disk ?: 'local');
        if (! $storage->exists($path)) {
            return;
        }

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
            if (stream_copy_to_stream($stream, $output) === false) {
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
    }

    private function addActivityLog(ZipArchive $zip, array &$fileIndex, Project $project, string $archivePath): void
    {
        $temporaryFile = $this->temporaryFile('mobilitycloud-activity-log-');
        $output = fopen($temporaryFile, 'w+b');
        if (! is_resource($output)) {
            throw new RuntimeException('Could not prepare the activity history for the final archive.');
        }

        try {
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'Date', 'User ID', 'Event', 'Subject type', 'Subject ID', 'Description', 'Metadata',
            ], ';');

            $project->activityLogs()
                ->orderBy('id')
                ->chunkById(500, function ($entries) use ($output): void {
                    foreach ($entries as $entry) {
                        fputcsv($output, [
                            $entry->created_at?->toIso8601String(),
                            $entry->user_id,
                            $entry->event,
                            $entry->subject_type,
                            $entry->subject_id,
                            $entry->description,
                            json_encode($entry->metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE),
                        ], ';');
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
    }

    private function temporaryFile(string $prefix): string
    {
        $temporaryFile = tempnam(sys_get_temp_dir(), $prefix);
        if ($temporaryFile === false) {
            throw new RuntimeException('Could not prepare a temporary file for the final archive.');
        }

        $this->temporaryFiles[] = $temporaryFile;

        return $temporaryFile;
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
