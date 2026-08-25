<?php

namespace Tests\Feature;

use App\Filament\Pages\PlatformHealth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlatformHealthBackupTest extends TestCase
{
    private string $workingPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workingPath = storage_path('framework/testing/platform-health-backup-'.Str::uuid());
        File::ensureDirectoryExists($this->workingPath);
        config()->set('mobilitycloud.external_backups.enabled', true);
        config()->set('mobilitycloud.external_backups.status_path', $this->workingPath.'/external.json');
        config()->set('mobilitycloud.external_backups.restore_status_path', $this->workingPath.'/restore.json');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->workingPath);

        parent::tearDown();
    }

    public function test_system_health_reports_recent_external_backup_and_restore_verification(): void
    {
        File::put($this->workingPath.'/external.json', json_encode([
            'status' => 'ok',
            'created_at' => now()->subHour()->toISOString(),
            'size_bytes' => 4096,
            'key_id' => '0123456789abcdef',
        ], JSON_THROW_ON_ERROR));
        File::put($this->workingPath.'/restore.json', json_encode([
            'status' => 'ok',
            'verified_at' => now()->subDay()->toISOString(),
            'storage_entries' => 12,
            'database' => ['tables_restored' => 42],
        ], JSON_THROW_ON_ERROR));

        $page = new class extends PlatformHealth
        {
            public function externalBackupStatus(): array
            {
                return $this->externalBackupCheck();
            }

            public function externalRestoreStatus(): array
            {
                return $this->externalRestoreCheck();
            }
        };

        $this->assertSame('ok', $page->externalBackupStatus()['level']);
        $this->assertSame('Encrypted external backup verified', $page->externalBackupStatus()['status']);
        $this->assertSame('ok', $page->externalRestoreStatus()['level']);
        $this->assertSame('External restore test passed', $page->externalRestoreStatus()['status']);
    }

    public function test_system_health_surfaces_an_external_backup_failure_without_leaking_a_secret(): void
    {
        File::put($this->workingPath.'/external.json', json_encode([
            'status' => 'failed',
            'recorded_at' => now()->toISOString(),
            'error' => 'token=super-secret-value upload failed',
        ], JSON_THROW_ON_ERROR));

        $page = new class extends PlatformHealth
        {
            public function externalBackupStatus(): array
            {
                return $this->externalBackupCheck();
            }
        };

        $status = $page->externalBackupStatus();

        $this->assertSame('bad', $status['level']);
        $this->assertStringNotContainsString('super-secret-value', $status['detail']);
        $this->assertStringContainsString('token=[redacted]', $status['detail']);
    }
}
