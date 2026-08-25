<?php

namespace App\Jobs;

use App\Models\ProjectActivityLog;
use App\Models\ProjectFinalArchive;
use App\Services\ProjectFinalArchiveService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class GenerateProjectFinalArchive implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 900;

    public function __construct(public int $archiveId)
    {
        $this->onQueue('default');
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(ProjectFinalArchiveService $archives): void
    {
        $archive = ProjectFinalArchive::query()->with('project')->find($this->archiveId);
        if (! $archive || ! $archive->project || ! in_array($archive->status, [
            ProjectFinalArchive::STATUS_QUEUED,
            ProjectFinalArchive::STATUS_PROCESSING,
        ], true)) {
            return;
        }

        $archive->forceFill([
            'status' => ProjectFinalArchive::STATUS_PROCESSING,
            'started_at' => now(),
            'failure_message' => null,
        ])->save();

        $temporaryPath = null;
        $storedPath = null;
        $disk = (string) config('mobilitycloud.final_archives.disk', 'local');

        try {
            $temporaryPath = $archives->create($archive->project, $archive->selection);
            $storedPath = 'final-archives/'.$archive->project_id.'/'.$archive->uuid.'.zip';
            $input = fopen($temporaryPath, 'rb');

            if (! is_resource($input)) {
                throw new RuntimeException('Could not read the generated archive.');
            }

            try {
                if (! Storage::disk($disk)->put($storedPath, $input)) {
                    throw new RuntimeException('Could not store the generated archive.');
                }
            } finally {
                fclose($input);
            }

            $size = filesize($temporaryPath);
            $sha256 = hash_file('sha256', $temporaryPath);
            if ($size === false || $sha256 === false) {
                throw new RuntimeException('Could not verify the generated archive.');
            }

            $archive->forceFill([
                'status' => ProjectFinalArchive::STATUS_READY,
                'disk' => $disk,
                'path' => $storedPath,
                'size' => $size,
                'sha256' => $sha256,
                'completed_at' => now(),
                'expires_at' => now()->addHours(max(1, (int) config('mobilitycloud.final_archives.retention_hours', 24))),
            ])->save();

            $newerReadyArchiveExists = $archive->project->finalArchives()
                ->where('id', '>', $archive->id)
                ->where('status', ProjectFinalArchive::STATUS_READY)
                ->exists();

            if ($newerReadyArchiveExists) {
                $archive->expire();
            } else {
                $archive->project->finalArchives()
                    ->where('id', '<', $archive->id)
                    ->where('status', ProjectFinalArchive::STATUS_READY)
                    ->get()
                    ->each->expire();
            }

            ProjectActivityLog::create([
                'project_id' => $archive->project_id,
                'user_id' => $archive->requested_by,
                'event' => 'final_archive_ready',
                'subject_type' => ProjectFinalArchive::class,
                'subject_id' => $archive->id,
                'description' => 'prepared the final project archive',
                'metadata' => [
                    'archive_uuid' => $archive->uuid,
                    'size' => $size,
                    'sha256' => $sha256,
                ],
            ]);
        } catch (Throwable $exception) {
            if ($storedPath !== null) {
                Storage::disk($disk)->delete($storedPath);
            }

            throw $exception;
        } finally {
            if ($temporaryPath !== null) {
                @unlink($temporaryPath);
            }
        }
    }

    public function failed(?Throwable $exception): void
    {
        $archive = ProjectFinalArchive::query()->with('project')->find($this->archiveId);
        if (! $archive) {
            return;
        }

        $archive->deleteStoredFile();
        $archive->forceFill([
            'status' => ProjectFinalArchive::STATUS_FAILED,
            'disk' => null,
            'path' => null,
            'failure_message' => 'The archive could not be prepared. Please try again.',
        ])->save();

        if ($archive->project) {
            ProjectActivityLog::create([
                'project_id' => $archive->project_id,
                'user_id' => $archive->requested_by,
                'event' => 'final_archive_failed',
                'subject_type' => ProjectFinalArchive::class,
                'subject_id' => $archive->id,
                'description' => 'could not prepare the final project archive',
                'metadata' => ['archive_uuid' => $archive->uuid],
            ]);
        }
    }
}
