<?php

namespace App\Console\Commands;

use App\Services\Backups\BackupFailureNotifier;
use App\Services\Backups\BackupStatusStore;
use App\Services\Backups\ExternalBackupService;
use Illuminate\Console\Command;
use Throwable;

class CreateMobilityCloudExternalBackup extends Command
{
    protected $signature = 'mobilitycloud:backup-external
        {--path= : Directory containing the local backup set to replicate}';

    protected $description = 'Encrypt, upload and verify the latest MobilityCloud backup on external object storage';

    public function handle(
        ExternalBackupService $service,
        BackupStatusStore $statusStore,
        BackupFailureNotifier $notifier,
    ): int {
        $statusPath = (string) config('mobilitycloud.external_backups.status_path');

        if (! config('mobilitycloud.external_backups.enabled')) {
            $this->warn('External backups are disabled.');

            return self::SUCCESS;
        }

        try {
            $result = $service->replicate($this->option('path') ?: null);
            $statusStore->write($statusPath, $result + [
                'status' => 'ok',
                'recorded_at' => now()->toISOString(),
            ]);

            $this->info(($result['already_present'] ?? false)
                ? 'External backup already exists and is available.'
                : 'External backup uploaded and verified.');
            $this->line((string) $result['object_path']);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $statusStore->failure($statusPath, $exception);
            $safeError = $statusStore->safeError($exception->getMessage());
            $notifier->send('External backup failed', $safeError);
            $this->error($safeError);

            return self::FAILURE;
        }
    }
}
