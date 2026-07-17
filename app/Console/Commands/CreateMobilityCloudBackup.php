<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Throwable;

class CreateMobilityCloudBackup extends Command
{
    protected $signature = 'mobilitycloud:backup
        {--path= : Directory where backup files are stored}
        {--retention-days= : Delete backup files older than this number of days}
        {--skip-storage : Only back up the database}
        {--no-retention : Do not delete old backup files}';

    protected $description = 'Create a production-safe MobilityCloud database and storage backup';

    public function handle(): int
    {
        $backupPath = rtrim((string) ($this->option('path') ?: config('mobilitycloud.backups.path')), '/');
        $retentionDays = (int) ($this->option('retention-days') ?: config('mobilitycloud.backups.retention_days', 14));
        $timestamp = now()->format('Ymd-His');

        File::ensureDirectoryExists($backupPath, 0750, true);

        $manifest = [
            'created_at' => now()->toISOString(),
            'environment' => app()->environment(),
            'app_url' => config('app.url'),
            'files' => [],
        ];

        try {
            $dbFile = "{$backupPath}/db-{$timestamp}.sql.gz";
            $this->dumpDatabase($dbFile);
            $manifest['files'][] = $this->fileManifest($dbFile, 'database');

            if (! $this->option('skip-storage')) {
                $storageFile = "{$backupPath}/storage-app-{$timestamp}.tar.gz";
                $this->archiveStorage($storageFile);
                $manifest['files'][] = $this->fileManifest($storageFile, 'storage_app');
            }

            $manifestFile = "{$backupPath}/manifest-{$timestamp}.json";
            File::put($manifestFile, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
            chmod($manifestFile, 0640);

            if (! $this->option('no-retention')) {
                $this->deleteOldBackups($backupPath, $retentionDays);
            }

            $this->info('Backup created.');
            $this->line($dbFile);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Backup failed: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    private function dumpDatabase(string $targetFile): void
    {
        $connectionName = config('database.default');
        $connection = config("database.connections.{$connectionName}");

        if (! in_array($connection['driver'] ?? null, ['mysql', 'mariadb'], true)) {
            throw new \RuntimeException('Only MySQL/MariaDB backups are supported by this command.');
        }

        $command = [
            'mysqldump',
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--default-character-set=utf8mb4',
        ];

        if (filled($connection['unix_socket'] ?? null)) {
            $command[] = '--socket='.$connection['unix_socket'];
        } else {
            $command[] = '-h';
            $command[] = (string) ($connection['host'] ?? '127.0.0.1');
            $command[] = '-P';
            $command[] = (string) ($connection['port'] ?? 3306);
        }

        $command[] = '-u';
        $command[] = (string) ($connection['username'] ?? 'root');
        $command[] = (string) ($connection['database'] ?? '');

        $gz = gzopen($targetFile, 'wb9');

        if (! $gz) {
            throw new \RuntimeException('Could not open database backup file for writing.');
        }

        $process = new Process($command, base_path(), [
            'MYSQL_PWD' => (string) ($connection['password'] ?? ''),
        ]);
        $process->setTimeout(3600);

        $process->run(function (string $type, string $buffer) use ($gz): void {
            if ($type === Process::OUT) {
                gzwrite($gz, $buffer);
            }
        });

        gzclose($gz);
        chmod($targetFile, 0640);

        if (! $process->isSuccessful()) {
            @unlink($targetFile);

            throw new \RuntimeException(trim($process->getErrorOutput()) ?: 'mysqldump exited unsuccessfully.');
        }
    }

    private function archiveStorage(string $targetFile): void
    {
        $storageAppPath = storage_path('app');

        if (! is_dir($storageAppPath)) {
            File::ensureDirectoryExists($storageAppPath, 0750, true);
        }

        $process = new Process([
            'tar',
            '-czf',
            $targetFile,
            '-C',
            storage_path(),
            'app',
        ]);
        $process->setTimeout(3600);
        $process->mustRun();

        chmod($targetFile, 0640);
    }

    private function fileManifest(string $file, string $type): array
    {
        return [
            'type' => $type,
            'path' => $file,
            'size_bytes' => filesize($file),
            'sha256' => hash_file('sha256', $file),
        ];
    }

    private function deleteOldBackups(string $backupPath, int $retentionDays): void
    {
        if ($retentionDays < 1) {
            return;
        }

        $threshold = now()->subDays($retentionDays)->getTimestamp();

        foreach (File::files($backupPath) as $file) {
            if ($file->getMTime() < $threshold) {
                File::delete($file->getPathname());
            }
        }
    }
}
