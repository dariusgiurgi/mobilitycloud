<?php

namespace Tests\Unit;

use App\Services\Backups\BackupStatusStore;
use App\Services\Backups\BackupStreamCipher;
use App\Support\SecretValue;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class BackupStreamCipherTest extends TestCase
{
    private string $workingPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workingPath = storage_path('framework/testing/backup-cipher-'.Str::uuid());
        File::ensureDirectoryExists($this->workingPath);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->workingPath);

        parent::tearDown();
    }

    public function test_it_encrypts_and_decrypts_a_stream_larger_than_one_chunk(): void
    {
        $cipher = app(BackupStreamCipher::class);
        $key = $this->workingPath.'/backup.key';
        $plain = $this->workingPath.'/plain.bin';
        $encrypted = $this->workingPath.'/plain.mcb';
        $restored = $this->workingPath.'/restored.bin';
        $contents = random_bytes((1024 * 1024 * 2) + 1537);
        File::put($plain, $contents);

        $keyId = $cipher->generateKeyFile($key);
        $metadata = $cipher->encrypt($plain, $encrypted, $key);
        $cipher->decrypt($encrypted, $restored, $key);

        $this->assertSame(16, strlen($keyId));
        $this->assertSame($keyId, $metadata['key_id']);
        $this->assertSame('mobilitycloud-secretstream-v1', $metadata['format']);
        $this->assertNotSame(hash_file('sha256', $plain), hash_file('sha256', $encrypted));
        $this->assertSame(hash_file('sha256', $plain), hash_file('sha256', $restored));
        $this->assertSame(0600, fileperms($key) & 0777);
    }

    public function test_it_supports_an_empty_file(): void
    {
        $cipher = app(BackupStreamCipher::class);
        $key = $this->workingPath.'/backup.key';
        $plain = $this->workingPath.'/empty.bin';
        $encrypted = $this->workingPath.'/empty.mcb';
        $restored = $this->workingPath.'/restored.bin';
        File::put($plain, '');
        $cipher->generateKeyFile($key);

        $cipher->encrypt($plain, $encrypted, $key);
        $cipher->decrypt($encrypted, $restored, $key);

        $this->assertSame('', File::get($restored));
    }

    public function test_it_rejects_a_corrupted_encrypted_backup(): void
    {
        $cipher = app(BackupStreamCipher::class);
        $key = $this->workingPath.'/backup.key';
        $plain = $this->workingPath.'/plain.bin';
        $encrypted = $this->workingPath.'/plain.mcb';
        $restored = $this->workingPath.'/restored.bin';
        File::put($plain, random_bytes(4096));
        $cipher->generateKeyFile($key);
        $cipher->encrypt($plain, $encrypted, $key);

        $handle = fopen($encrypted, 'r+b');
        $this->assertIsResource($handle);
        fseek($handle, 100);
        fwrite($handle, "\x00\x01\x02\x03");
        fclose($handle);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('corrupt or its key is incorrect');

        $cipher->decrypt($encrypted, $restored, $key);
    }

    public function test_status_updates_replace_a_read_only_previous_record_atomically(): void
    {
        $path = $this->workingPath.'/external-health.json';
        File::put($path, json_encode(['status' => 'failed'], JSON_THROW_ON_ERROR));
        chmod($path, 0400);

        app(BackupStatusStore::class)->write($path, [
            'status' => 'ok',
            'recorded_at' => now()->toISOString(),
        ]);

        $status = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('ok', $status['status']);
        $this->assertSame(0640, fileperms($path) & 0777);
        $this->assertSame([], File::glob($path.'.*.tmp'));
    }

    public function test_a_protected_secret_file_can_supply_a_missing_environment_value(): void
    {
        $path = $this->workingPath.'/provider-secret';
        File::put($path, "secret-from-file\n");

        $this->assertSame('direct-secret', SecretValue::resolve('direct-secret', $path));
        $this->assertSame('secret-from-file', SecretValue::resolve(null, $path));
        $this->assertNull(SecretValue::resolve(null, $this->workingPath.'/missing'));
    }
}
