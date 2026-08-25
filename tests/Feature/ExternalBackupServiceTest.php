<?php

namespace Tests\Feature;

use App\Services\Backups\BackupStreamCipher;
use App\Services\Backups\ExternalBackupRestoreService;
use App\Services\Backups\ExternalBackupService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ExternalBackupServiceTest extends TestCase
{
    private string $workingPath;

    private string $backupPath;

    private string $keyPath;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('external_backups');
        $this->workingPath = storage_path('framework/testing/external-backup-'.Str::uuid());
        $this->backupPath = $this->workingPath.'/local';
        $this->keyPath = $this->workingPath.'/backup.key';
        File::ensureDirectoryExists($this->backupPath);
        app(BackupStreamCipher::class)->generateKeyFile($this->keyPath);

        config()->set('mobilitycloud.external_backups.disk', 'external_backups');
        config()->set('mobilitycloud.external_backups.prefix', 'mobilitycloud-tests');
        config()->set('mobilitycloud.external_backups.key_path', $this->keyPath);

        $this->createBackupSet();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->workingPath);

        parent::tearDown();
    }

    public function test_it_encrypts_uploads_and_verifies_the_latest_local_backup(): void
    {
        $result = app(ExternalBackupService::class)->replicate($this->backupPath);

        $this->assertFalse($result['already_present']);
        $this->assertTrue($result['verified_after_upload']);
        Storage::disk('external_backups')->assertExists($result['object_path']);
        Storage::disk('external_backups')->assertExists($result['metadata_path']);
        $this->assertSame($result['size_bytes'], Storage::disk('external_backups')->size($result['object_path']));
        $this->assertStringNotContainsString('CREATE TABLE', Storage::disk('external_backups')->get($result['object_path']));
    }

    public function test_replicating_the_same_backup_is_idempotent(): void
    {
        $first = app(ExternalBackupService::class)->replicate($this->backupPath);
        $second = app(ExternalBackupService::class)->replicate($this->backupPath);

        $this->assertTrue($second['already_present']);
        $this->assertSame($first['object_path'], $second['object_path']);
        $this->assertCount(1, Storage::disk('external_backups')->allFiles('mobilitycloud-tests/snapshots'));
    }

    public function test_it_downloads_decrypts_and_checks_an_external_snapshot(): void
    {
        $uploaded = app(ExternalBackupService::class)->replicate($this->backupPath);

        $result = app(ExternalBackupRestoreService::class)->verify(function (string $extractedPath, array $set): array {
            $this->assertFileExists($set['files']['database']['path']);
            $this->assertFileExists($set['files']['storage_app']['path']);
            $this->assertStringStartsWith(storage_path('app/private/backup-restore-work/'), $extractedPath);

            return [
                'tables_restored' => 42,
                'migrations_table_verified' => true,
            ];
        });

        $this->assertSame($uploaded['backup_id'], $result['backup_id']);
        $this->assertSame(42, $result['database']['tables_restored']);
        $this->assertGreaterThan(0, $result['storage_entries']);
    }

    public function test_restore_rejects_remote_data_that_no_longer_matches_its_hash(): void
    {
        $uploaded = app(ExternalBackupService::class)->replicate($this->backupPath);
        Storage::disk('external_backups')->put($uploaded['object_path'], 'tampered');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('size does not match');

        app(ExternalBackupRestoreService::class)->verify(fn (): array => []);
    }

    private function createBackupSet(): void
    {
        $timestamp = '20260825-021500';
        $databasePath = $this->backupPath.'/db-'.$timestamp.'.sql.gz';
        $storagePath = $this->backupPath.'/storage-app-'.$timestamp.'.tar.gz';
        $manifestPath = $this->backupPath.'/manifest-'.$timestamp.'.json';
        File::put($databasePath, gzencode("CREATE TABLE migrations (id INT);\n"));

        $storageSource = $this->workingPath.'/storage/app/private';
        File::ensureDirectoryExists($storageSource);
        File::put($storageSource.'/document.txt', 'private project document');
        $process = new Process(['tar', '-czf', $storagePath, '-C', $this->workingPath.'/storage', 'app']);
        $process->mustRun();

        $manifest = [
            'created_at' => '2026-08-25T02:15:00+03:00',
            'environment' => 'testing',
            'app_url' => 'https://mobilitycloud.test',
            'files' => [
                [
                    'type' => 'database',
                    'path' => $databasePath,
                    'size_bytes' => filesize($databasePath),
                    'sha256' => hash_file('sha256', $databasePath),
                ],
                [
                    'type' => 'storage_app',
                    'path' => $storagePath,
                    'size_bytes' => filesize($storagePath),
                    'sha256' => hash_file('sha256', $storagePath),
                ],
            ],
        ];

        File::put($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }
}
