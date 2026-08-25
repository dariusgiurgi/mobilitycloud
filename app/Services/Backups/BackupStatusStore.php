<?php

namespace App\Services\Backups;

use Illuminate\Support\Facades\File;
use Throwable;

class BackupStatusStore
{
    public function write(string $path, array $payload): void
    {
        if ($path === '') {
            return;
        }

        File::ensureDirectoryExists(dirname($path), 0750, true);
        $temporaryPath = $path.'.'.bin2hex(random_bytes(6)).'.tmp';
        File::put($temporaryPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
        @chmod($temporaryPath, 0640);

        if (! rename($temporaryPath, $path)) {
            @unlink($temporaryPath);

            throw new \RuntimeException('Could not replace the backup status record.');
        }
    }

    public function failure(string $path, Throwable $exception, array $context = []): void
    {
        $this->write($path, $context + [
            'status' => 'failed',
            'recorded_at' => now()->toISOString(),
            'error' => $this->safeError($exception->getMessage()),
        ]);
    }

    public function safeError(string $message): string
    {
        $firstLine = trim((string) (preg_split('/\R/', $message)[0] ?? $message));

        return mb_substr(
            preg_replace('/(?i)(password|secret|token|key)\s*(?:=|:|=>)\s*[^\s,}]+/', '$1=[redacted]', $firstLine) ?? $firstLine,
            0,
            500,
        );
    }
}
