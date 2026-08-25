<?php

namespace App\Console\Commands;

use App\Services\Backups\BackupFailureNotifier;
use App\Services\Backups\BackupStatusStore;
use App\Services\Backups\ExternalBackupRestoreService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;
use Throwable;

class TestMobilityCloudExternalBackupRestore extends Command
{
    protected $signature = 'mobilitycloud:backup-external-restore-test';

    protected $description = 'Download, decrypt and restore the latest external backup into a disposable database';

    public function handle(
        ExternalBackupRestoreService $service,
        BackupStatusStore $statusStore,
        BackupFailureNotifier $notifier,
    ): int {
        $statusPath = (string) config('mobilitycloud.external_backups.restore_status_path');

        if (! config('mobilitycloud.external_backups.enabled')) {
            $this->warn('External backups are disabled.');

            return self::SUCCESS;
        }

        try {
            $result = $service->verify(function (string $backupPath): array {
                $parameters = ['--path' => $backupPath];
                $mapping = [
                    '--database' => 'database',
                    '--mysql-user' => 'mysql_user',
                    '--mysql-password' => 'mysql_password',
                    '--mysql-host' => 'mysql_host',
                    '--mysql-port' => 'mysql_port',
                ];

                foreach ($mapping as $option => $configKey) {
                    $value = config('mobilitycloud.external_backups.restore.'.$configKey);

                    if (filled($value)) {
                        $parameters[$option] = (string) $value;
                    }
                }

                $exitCode = Artisan::call('mobilitycloud:backup-restore-test', $parameters);
                $output = Artisan::output();

                if ($exitCode !== self::SUCCESS) {
                    throw new RuntimeException(trim($output) ?: 'The disposable database restore failed.');
                }

                preg_match('/Tables restored:\s*(\d+)/', $output, $matches);

                return [
                    'tables_restored' => (int) ($matches[1] ?? 0),
                    'migrations_table_verified' => true,
                ];
            });

            $statusStore->write($statusPath, $result + [
                'status' => 'ok',
                'recorded_at' => now()->toISOString(),
            ]);

            $this->info('External backup restore test passed.');
            $this->line('Backup: '.$result['backup_id']);
            $this->line('Tables restored: '.$result['database']['tables_restored']);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $statusStore->failure($statusPath, $exception);
            $safeError = $statusStore->safeError($exception->getMessage());
            $notifier->send('External backup restore test failed', $safeError);
            $this->error($safeError);

            return self::FAILURE;
        }
    }
}
