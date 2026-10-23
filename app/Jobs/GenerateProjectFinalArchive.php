<?php

namespace App\Jobs;

use App\Models\ProjectActivityLog;
use App\Models\ProjectFinalArchive;
use App\Services\ProjectFinalArchiveService;
use App\Support\ArchivePerformance;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        $this->onQueue((string) config('mobilitycloud.final_archives.queue', 'exports'));
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('final-archive:'.$this->archiveId))
            ->releaseAfter(30)->expireAfter(960)];
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

        $performance = new ArchivePerformance;
        $requestAgeMs = max(0, (int) $archive->created_at->diffInMilliseconds(now()));
        // On retries this age includes previous work and backoff, not just queue wait.
        $queueWaitMs = $archive->status === ProjectFinalArchive::STATUS_QUEUED ? $requestAgeMs : null;
        $committed = false;
        $size = null;
        $temporaryPath = null;
        $storedPath = null;
        $disk = (string) config('mobilitycloud.final_archives.disk', 'local');

        try {
            $performance->measure('archive_start', fn () => $archive->forceFill([
                'status' => ProjectFinalArchive::STATUS_PROCESSING,
                'started_at' => now(),
                'failure_message' => null,
            ])->save());

            $temporaryPath = $archives->create($archive->project, $archive->selection, $performance);
            $storedPath = 'final-archives/'.$archive->project_id.'/'.$archive->uuid.'.zip';
            $performance->measure('archive_store', function () use ($temporaryPath, $disk, $storedPath): void {
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
            });

            [$size, $sha256] = $performance->measure('archive_verify', function () use ($temporaryPath): array {
                $size = filesize($temporaryPath);
                $sha256 = hash_file('sha256', $temporaryPath);
                if ($size === false || $sha256 === false) {
                    throw new RuntimeException('Could not verify the generated archive.');
                }

                return [$size, $sha256];
            });

            $performance->measure('archive_commit', fn () => DB::transaction(function () use ($archive, $disk, $storedPath, $size, $sha256): void {
                $archive->forceFill([
                    'status' => ProjectFinalArchive::STATUS_READY,
                    'disk' => $disk,
                    'path' => $storedPath,
                    'size' => $size,
                    'sha256' => $sha256,
                    'completed_at' => now(),
                    'expires_at' => now()->addHours(max(1, (int) config('mobilitycloud.final_archives.retention_hours', 24))),
                ])->save();

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
            }));
            $committed = true;

            try {
                $performance->measure('archive_cleanup', function () use ($archive): void {
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
                });
            } catch (Throwable $cleanupError) {
                report($cleanupError);
            }
        } catch (Throwable $exception) {
            if (! $committed && $storedPath !== null) {
                Storage::disk($disk)->delete($storedPath);
            }

            throw $exception;
        } finally {
            if ($temporaryPath !== null) {
                @unlink($temporaryPath);
            }
            try {
                Log::channel('performance')->info('Final archive attempt', [
                    'archive_id' => $archive->id, 'attempt' => $this->attempts(),
                    'outcome' => $committed ? 'ready' : 'failed', 'bytes' => $size,
                    'queue_wait_ms' => $queueWaitMs, 'request_age_ms' => $requestAgeMs,
                    ...$performance->summary(),
                ]);
            } catch (Throwable) {
                // Performance logging must never fail an export or hide its original error.
            }
        }
    }

    public function failed(?Throwable $exception): void
    {
        $archive = ProjectFinalArchive::query()->with('project')->find($this->archiveId);
        if (! $archive || $archive->status === ProjectFinalArchive::STATUS_READY) {
            return;
        }

        $archive->deleteStoredFile();
        $archive->forceFill([
            'status' => ProjectFinalArchive::STATUS_FAILED,
            'disk' => null,
            'path' => null,
            'failure_message' => str_starts_with($exception?->getMessage() ?? '', 'A referenced archive file is missing:')
                ? $exception->getMessage() : 'The archive could not be prepared. Please try again.',
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
