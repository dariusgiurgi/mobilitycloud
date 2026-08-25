<?php

namespace Tests\Unit;

use App\Services\Backups\LocalBackupRetention;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class LocalBackupRetentionTest extends TestCase
{
    private string $backupPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupPath = storage_path('framework/testing/local-backup-retention-'.Str::uuid());
        File::ensureDirectoryExists($this->backupPath);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->backupPath);

        parent::tearDown();
    }

    public function test_it_only_deletes_expired_files_owned_by_the_backup_command(): void
    {
        $expired = [
            'db-20260701-021500.sql.gz',
            'storage-app-20260701-021500.tar.gz',
            'manifest-20260701-021500.json',
        ];
        $protected = [
            'recovery-notes.txt',
            'db-manual.sql.gz',
            'manifest-20260701-021500.json.signature',
        ];

        foreach ([...$expired, ...$protected] as $filename) {
            $path = $this->backupPath.'/'.$filename;
            File::put($path, 'test');
            touch($path, now()->subDays(40)->getTimestamp());
        }

        $recent = $this->backupPath.'/db-20260825-021500.sql.gz';
        File::put($recent, 'recent');

        $deleted = app(LocalBackupRetention::class)->purge($this->backupPath, 14);

        $this->assertSame(3, $deleted);
        foreach ($expired as $filename) {
            $this->assertFileDoesNotExist($this->backupPath.'/'.$filename);
        }
        foreach ($protected as $filename) {
            $this->assertFileExists($this->backupPath.'/'.$filename);
        }
        $this->assertFileExists($recent);
    }

    public function test_zero_retention_days_never_deletes_files(): void
    {
        $path = $this->backupPath.'/db-20260701-021500.sql.gz';
        File::put($path, 'test');
        touch($path, now()->subDays(40)->getTimestamp());

        $this->assertSame(0, app(LocalBackupRetention::class)->purge($this->backupPath, 0));
        $this->assertFileExists($path);
    }
}
