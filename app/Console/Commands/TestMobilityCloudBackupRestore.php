<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Throwable;

class TestMobilityCloudBackupRestore extends Command
{
    protected $signature = 'mobilitycloud:backup-restore-test
        {--path= : Directory where backup files are stored}
        {--database= : Temporary database name to use}
        {--mysql-user= : MySQL user for the restore test; omit for local root socket auth}
        {--mysql-password= : MySQL password for the restore test}
        {--mysql-host=127.0.0.1 : MySQL host}
        {--mysql-port=3306 : MySQL port}
        {--keep-database : Do not drop the temporary restore database after the test}';

    protected $description = 'Restore the latest database backup into a temporary database to verify that backups are usable';

    public function handle(): int
    {
        $backupPath = rtrim((string) ($this->option('path') ?: config('mobilitycloud.backups.path')), '/');
        $latestBackup = $this->latestDatabaseBackup($backupPath);

        if (! $latestBackup) {
            $this->error('No database backup found in '.$backupPath.'.');

            return self::FAILURE;
        }

        $database = (string) ($this->option('database') ?: 'mobilitycloud_restore_test_'.now()->format('YmdHis'));

        if (! preg_match('/^[A-Za-z0-9_]+$/', $database)) {
            $this->error('Temporary database name may only contain letters, numbers and underscores.');

            return self::FAILURE;
        }

        try {
            $this->line('Testing restore from '.$latestBackup);
            $this->mysql(['-e', 'CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci']);
            $this->importBackup($latestBackup, $database);

            $tableCount = trim($this->mysql(['--batch', '--skip-column-names', '-e', 'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = \''.$database.'\''], capture: true));

            if ((int) $tableCount < 1) {
                throw new \RuntimeException('Restore completed, but no tables were found.');
            }

            $this->info('Restore test passed. Tables restored: '.$tableCount);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Restore test failed: '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            if (! $this->option('keep-database')) {
                try {
                    $this->mysql(['-e', 'DROP DATABASE IF EXISTS `'.$database.'`']);
                } catch (Throwable) {
                    $this->warn('Could not drop temporary restore database '.$database.'.');
                }
            }
        }
    }

    private function latestDatabaseBackup(string $backupPath): ?string
    {
        if (! is_dir($backupPath)) {
            return null;
        }

        return collect(File::glob($backupPath.'/db-*.sql.gz'))
            ->sortByDesc(fn (string $file): int => filemtime($file) ?: 0)
            ->first();
    }

    private function mysql(array $arguments, bool $capture = false): string
    {
        $command = ['mysql'];

        if (filled($this->option('mysql-user'))) {
            array_push($command, '-u', (string) $this->option('mysql-user'));
        }

        array_push($command, '-h', (string) $this->option('mysql-host'), '-P', (string) $this->option('mysql-port'));

        $command = array_merge($command, $arguments);

        $process = new Process($command, base_path(), [
            'MYSQL_PWD' => (string) ($this->option('mysql-password') ?: ''),
        ]);
        $process->setTimeout(3600);
        $process->mustRun();

        return $capture ? $process->getOutput() : '';
    }

    private function importBackup(string $backupFile, string $database): void
    {
        $mysqlCommand = ['mysql'];

        if (filled($this->option('mysql-user'))) {
            array_push($mysqlCommand, '-u', (string) $this->option('mysql-user'));
        }

        array_push($mysqlCommand, '-h', (string) $this->option('mysql-host'), '-P', (string) $this->option('mysql-port'), $database);

        $shell = 'gzip -dc '.escapeshellarg($backupFile).' | '.implode(' ', array_map('escapeshellarg', $mysqlCommand));

        $process = Process::fromShellCommandline($shell, base_path(), [
            'MYSQL_PWD' => (string) ($this->option('mysql-password') ?: ''),
        ]);
        $process->setTimeout(3600);
        $process->mustRun();
    }
}
