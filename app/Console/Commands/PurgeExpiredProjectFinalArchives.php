<?php

namespace App\Console\Commands;

use App\Models\ProjectActivityLog;
use App\Models\ProjectFinalArchive;
use Illuminate\Console\Command;

class PurgeExpiredProjectFinalArchives extends Command
{
    protected $signature = 'mobilitycloud:purge-final-archives';

    protected $description = 'Remove expired temporary final project ZIP files';

    public function handle(): int
    {
        $expiredCount = 0;
        ProjectFinalArchive::query()
            ->where('status', ProjectFinalArchive::STATUS_READY)
            ->where('expires_at', '<=', now())
            ->chunkById(100, function ($archives) use (&$expiredCount): void {
                foreach ($archives as $archive) {
                    $archive->expire();
                    $expiredCount++;
                }
            });

        $staleCount = 0;
        ProjectFinalArchive::query()
            ->whereIn('status', [ProjectFinalArchive::STATUS_QUEUED, ProjectFinalArchive::STATUS_PROCESSING])
            ->where('updated_at', '<=', now()->subHours(2))
            ->chunkById(100, function ($archives) use (&$staleCount): void {
                foreach ($archives as $archive) {
                    $archive->deleteStoredFile();
                    $archive->forceFill([
                        'status' => ProjectFinalArchive::STATUS_FAILED,
                        'disk' => null,
                        'path' => null,
                        'failure_message' => 'The archive did not finish in time. Please try again.',
                    ])->save();

                    ProjectActivityLog::create([
                        'project_id' => $archive->project_id,
                        'user_id' => $archive->requested_by,
                        'event' => 'final_archive_failed',
                        'subject_type' => ProjectFinalArchive::class,
                        'subject_id' => $archive->id,
                        'description' => 'could not prepare the final project archive',
                        'metadata' => [
                            'archive_uuid' => $archive->uuid,
                            'reason' => 'stalled',
                        ],
                    ]);
                    $staleCount++;
                }
            });

        $this->info('Expired '.$expiredCount.' archive(s) and released '.$staleCount.' stalled request(s).');

        return self::SUCCESS;
    }
}
