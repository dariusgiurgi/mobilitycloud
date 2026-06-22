<?php

namespace App\Services;

use App\Models\BudgetLine;
use App\Models\BudgetTransfer;
use App\Models\ContentBlock;
use App\Models\Expense;
use App\Models\Participant;
use App\Models\ParticipantAttachment;
use App\Models\Project;
use App\Models\ProjectApplicationSection;
use App\Models\ProjectDocument;
use App\Models\ProjectTask;
use App\Models\SavedCalculation;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;
use ZipArchive;

class WorkspaceRestoreService
{
    private array $writtenFiles = [];

    public function restore(Workspace $workspace, string $archivePath): array
    {
        $zip = new ZipArchive;
        if ($zip->open($archivePath) !== true) {
            throw ValidationException::withMessages(['restoreFile' => 'The selected file is not a readable ZIP archive.']);
        }

        try {
            $raw = $zip->getFromName('workspace-data.json');
            if ($raw === false || strlen($raw) > 50 * 1024 * 1024) {
                throw ValidationException::withMessages(['restoreFile' => 'The archive does not contain a valid MobilityCloud data file.']);
            }

            $data = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
            $version = (int) ($data['format_version'] ?? 0);
            if (! in_array($version, [1, 2], true) || ! is_array($data['projects'] ?? null)) {
                throw ValidationException::withMessages(['restoreFile' => 'This backup format cannot be restored. Create a new backup and try again.']);
            }

            $fileRows = $version === 1
                ? $this->legacyFileIndex($data, $zip)
                : ($data['file_index'] ?? []);
            if (! is_array($fileRows) || count($fileRows) > 5000 || collect($fileRows)->sum(fn ($file): int => (int) ($file['size'] ?? 0)) > 500 * 1024 * 1024) {
                throw ValidationException::withMessages(['restoreFile' => 'The backup contains too many files or exceeds the safe extraction limit.']);
            }

            $index = collect($fileRows)->keyBy(
                fn (array $file): string => $file['entity'].':'.$file['record_id'].':'.$file['slot'],
            );
            $batch = (string) Str::uuid();

            return DB::transaction(fn (): array => Model::withoutEvents(function () use ($workspace, $data, $index, $zip, $batch): array {
                $stats = ['projects' => 0, 'participants' => 0, 'expenses' => 0, 'documents' => 0, 'files' => 0, 'content_blocks' => 0];

                if (empty($workspace->document_settings) && is_array($data['workspace']['document_settings'] ?? null)) {
                    $workspace->document_settings = $data['workspace']['document_settings'];
                }
                if (! $workspace->document_logo_path) {
                    $logo = $this->restoreIndexedFile($zip, $index, $batch, 'workspace', (int) ($data['workspace']['id'] ?? 0), 'document_logo');
                    if ($logo) {
                        $workspace->document_logo_path = $logo['path'];
                        $stats['files']++;
                    }
                }
                if ($workspace->isDirty()) {
                    $workspace->save();
                }

                foreach ($data['content_library'] ?? [] as $row) {
                    $attributes = Arr::only($row, (new ContentBlock)->getFillable());
                    $attributes['workspace_id'] = $workspace->id;
                    $attributes['imported_from_public_id'] = null;
                    $attributes['title'] = $this->uniqueContentTitle($workspace, $attributes['title'] ?? 'Restored content');
                    ContentBlock::create($attributes);
                    $stats['content_blocks']++;
                }

                foreach ($data['saved_calculations'] ?? [] as $row) {
                    $attributes = Arr::only($row, (new SavedCalculation)->getFillable());
                    $attributes['workspace_id'] = $workspace->id;
                    $attributes['created_by'] = auth()->id();
                    SavedCalculation::create($attributes);
                }

                foreach ($data['projects'] as $bundle) {
                    $oldProject = $bundle['project'] ?? [];
                    $attributes = Arr::only($oldProject, (new Project)->getFillable());
                    $attributes['workspace_id'] = $workspace->id;
                    $attributes['access_mode'] = 'workspace';
                    $attributes['activation_payment_id'] = null;
                    $attributes['name'] = $this->uniqueProjectName($workspace, $attributes['name'] ?? 'Restored project');
                    $project = Project::create($attributes);
                    $lineMap = [];

                    foreach ($bundle['application_sections'] ?? [] as $row) {
                        ProjectApplicationSection::create([
                            ...Arr::only($row, (new ProjectApplicationSection)->getFillable()),
                            'project_id' => $project->id,
                        ]);
                    }

                    foreach ($bundle['budget_lines'] ?? [] as $lineRow) {
                        $line = BudgetLine::create([
                            ...Arr::only($lineRow, (new BudgetLine)->getFillable()),
                            'project_id' => $project->id,
                        ]);
                        $lineMap[(int) ($lineRow['id'] ?? 0)] = $line->id;

                        foreach ($lineRow['expenses'] ?? [] as $expenseRow) {
                            $oldId = (int) ($expenseRow['id'] ?? 0);
                            $expenseData = Arr::only($expenseRow, (new Expense)->getFillable());
                            $expenseData['budget_line_id'] = $line->id;
                            $expenseData['created_by'] = null;
                            $expenseData['attachment_path'] = null;
                            $expenseData['attachment_disk'] = 'local';
                            $expense = Expense::create($expenseData);
                            $this->restoreExpenseFiles($zip, $index, $batch, $expense, $oldId, $stats);
                            if (! empty($expenseRow['deleted_at'])) {
                                $expense->delete();
                            }
                            $stats['expenses']++;
                        }
                    }

                    foreach ($bundle['budget_transfers'] ?? [] as $row) {
                        $from = $lineMap[(int) ($row['from_budget_line_id'] ?? 0)] ?? null;
                        $to = $lineMap[(int) ($row['to_budget_line_id'] ?? 0)] ?? null;
                        if (! $from || ! $to) {
                            continue;
                        }
                        BudgetTransfer::create([
                            ...Arr::only($row, (new BudgetTransfer)->getFillable()),
                            'project_id' => $project->id,
                            'from_budget_line_id' => $from,
                            'to_budget_line_id' => $to,
                            'created_by' => null,
                        ]);
                    }

                    foreach ($bundle['participants'] ?? [] as $participantRow) {
                        $participant = Participant::create([
                            ...Arr::only($participantRow, (new Participant)->getFillable()),
                            'project_id' => $project->id,
                        ]);
                        foreach ($participantRow['attachments'] ?? [] as $attachmentRow) {
                            $oldId = (int) ($attachmentRow['id'] ?? 0);
                            $stored = $this->restoreIndexedFile($zip, $index, $batch, 'participant_attachment', $oldId, 'file');
                            if (! $stored) {
                                continue;
                            }
                            ParticipantAttachment::create([
                                ...Arr::only($attachmentRow, (new ParticipantAttachment)->getFillable()),
                                'participant_id' => $participant->id,
                                'path' => $stored['path'],
                                'disk' => 'local',
                                'size' => $stored['size'],
                            ]);
                            $stats['files']++;
                        }
                        $stats['participants']++;
                    }

                    foreach ($bundle['documents'] ?? [] as $row) {
                        $oldId = (int) ($row['id'] ?? 0);
                        $documentData = Arr::only($row, (new ProjectDocument)->getFillable());
                        $documentData['project_id'] = $project->id;
                        foreach (['file_path', 'signed_path'] as $field) {
                            $documentData[$field] = null;
                        }
                        $documentData['file_disk'] = $documentData['signed_disk'] = 'local';
                        $original = $this->restoreIndexedFile($zip, $index, $batch, 'project_document', $oldId, 'original');
                        $signed = $this->restoreIndexedFile($zip, $index, $batch, 'project_document', $oldId, 'signed');
                        if ($original) {
                            $documentData['file_path'] = $original['path'];
                            $documentData['file_size'] = $original['size'];
                            $stats['files']++;
                        }
                        if ($signed) {
                            $documentData['signed_path'] = $signed['path'];
                            $documentData['signed_size'] = $signed['size'];
                            $stats['files']++;
                        }
                        ProjectDocument::create($documentData);
                        $stats['documents']++;
                    }

                    foreach ($bundle['tasks'] ?? [] as $row) {
                        ProjectTask::create([
                            ...Arr::only($row, (new ProjectTask)->getFillable()),
                            'project_id' => $project->id,
                            'assigned_to' => null,
                            'created_by' => null,
                            'completed_by' => null,
                            'reminder_sent_at' => null,
                            'overdue_notified_at' => null,
                        ]);
                    }

                    if (! empty($oldProject['deleted_at'])) {
                        $project->delete();
                    }
                    $stats['projects']++;
                }

                return $stats;
            }));
        } catch (Throwable $exception) {
            foreach ($this->writtenFiles as $path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        } finally {
            $zip->close();
        }
    }

    private function restoreExpenseFiles(ZipArchive $zip, $index, string $batch, Expense $expense, int $oldId, array &$stats): void
    {
        $evidence = $this->restoreIndexedFile($zip, $index, $batch, 'expense', $oldId, 'evidence');
        $convention = $expense->convention_data ?? [];
        if ($evidence) {
            $expense->attachment_path = $evidence['path'];
            $expense->attachment_disk = 'local';
            $stats['files']++;
        }
        foreach (['agreement', 'acceptance', 'payment'] as $kind) {
            $file = $this->restoreIndexedFile($zip, $index, $batch, 'expense', $oldId, $kind);
            if (! $file) {
                continue;
            }
            $convention[$kind.'_signed_path'] = $file['path'];
            $convention[$kind.'_signed_disk'] = 'local';
            $convention[$kind.'_signed_size'] = $file['size'];
            $stats['files']++;
        }
        $expense->convention_data = $convention;
        $expense->save();
    }

    private function restoreIndexedFile(ZipArchive $zip, $index, string $batch, string $entity, int $recordId, string $slot): ?array
    {
        $entry = $index->get($entity.':'.$recordId.':'.$slot);
        if (! is_array($entry) || ! str_starts_with($entry['archive_path'] ?? '', 'files/')) {
            return null;
        }
        $contents = $zip->getFromName($entry['archive_path']);
        if ($contents === false || hash('sha256', $contents) !== ($entry['sha256'] ?? '')) {
            throw ValidationException::withMessages(['restoreFile' => 'A file in the backup is missing or failed its integrity check.']);
        }
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '-', basename((string) ($entry['original_name'] ?: 'file'))) ?: 'file';
        $path = 'workspace-restores/'.$batch.'/'.$entity.'-'.$recordId.'-'.$slot.'-'.$name;
        Storage::disk('local')->put($path, $contents);
        $this->writtenFiles[] = $path;

        return ['path' => $path, 'size' => strlen($contents)];
    }

    private function uniqueProjectName(Workspace $workspace, string $name): string
    {
        if (! $workspace->projects()->withTrashed()->where('name', $name)->exists()) {
            return $name;
        }

        return $name.' (restored '.now()->format('Y-m-d H:i').')';
    }

    private function uniqueContentTitle(Workspace $workspace, string $title): string
    {
        return $workspace->contentBlocks()->where('title', $title)->exists()
            ? $title.' (restored)'
            : $title;
    }

    private function legacyFileIndex(array $data, ZipArchive $zip): array
    {
        $rows = [];
        $workspace = $data['workspace'] ?? [];
        if (! empty($workspace['document_logo_path'])) {
            $this->appendLegacyFile($rows, $zip, 'workspace', (int) ($workspace['id'] ?? 0), 'document_logo', 'files/workspace/document-logo-'.$this->safeFilename($workspace['document_logo_path']), basename($workspace['document_logo_path']));
        }
        foreach ($data['projects'] as $bundle) {
            $project = $bundle['project'] ?? [];
            $projectDir = 'files/'.($project['id'] ?? 0).'-'.$this->safeName($project['name'] ?? 'project');
            foreach ($bundle['participants'] ?? [] as $participant) {
                $participantDir = $projectDir.'/participants/'.($participant['id'] ?? 0).'-'.$this->safeName(trim(($participant['first_name'] ?? '').' '.($participant['last_name'] ?? '')));
                foreach ($participant['attachments'] ?? [] as $attachment) {
                    $this->appendLegacyFile($rows, $zip, 'participant_attachment', (int) ($attachment['id'] ?? 0), 'file', $participantDir.'/'.($attachment['id'] ?? 0).'-'.$this->safeFilename($attachment['original_name'] ?? null), $attachment['original_name'] ?? null);
                }
            }
            foreach ($bundle['budget_lines'] ?? [] as $line) {
                foreach ($line['expenses'] ?? [] as $expense) {
                    $id = (int) ($expense['id'] ?? 0);
                    $this->appendLegacyFile($rows, $zip, 'expense', $id, 'evidence', $projectDir.'/expenses/'.$id.'-'.$this->safeFilename($expense['attachment_name'] ?? null), $expense['attachment_name'] ?? null);
                    $convention = $expense['convention_data'] ?? [];
                    foreach (['agreement', 'acceptance', 'payment'] as $kind) {
                        $this->appendLegacyFile($rows, $zip, 'expense', $id, $kind, $projectDir.'/civil-conventions/'.$id.'-'.$kind.'-'.$this->safeFilename($convention[$kind.'_signed_name'] ?? null), $convention[$kind.'_signed_name'] ?? null);
                    }
                }
            }
            foreach ($bundle['documents'] ?? [] as $document) {
                $id = (int) ($document['id'] ?? 0);
                $this->appendLegacyFile($rows, $zip, 'project_document', $id, 'original', $projectDir.'/documents/'.$id.'-original-'.$this->safeFilename($document['file_name'] ?? null), $document['file_name'] ?? null);
                $this->appendLegacyFile($rows, $zip, 'project_document', $id, 'signed', $projectDir.'/documents/'.$id.'-signed-'.$this->safeFilename($document['signed_name'] ?? null), $document['signed_name'] ?? null);
            }
        }

        return $rows;
    }

    private function appendLegacyFile(array &$rows, ZipArchive $zip, string $entity, int $recordId, string $slot, string $archivePath, ?string $name): void
    {
        $contents = $zip->getFromName($archivePath);
        if ($contents === false) {
            return;
        }
        $rows[] = [
            'entity' => $entity,
            'record_id' => $recordId,
            'slot' => $slot,
            'archive_path' => $archivePath,
            'original_name' => $name,
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
