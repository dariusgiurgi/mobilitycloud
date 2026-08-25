<?php

namespace App\Services\Backups;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use RuntimeException;

class LocalBackupSetLocator
{
    public function latest(string $backupPath): array
    {
        $backupPath = rtrim($backupPath, '/');

        if (! is_dir($backupPath)) {
            throw new RuntimeException('The local backup directory does not exist.');
        }

        $manifestPath = collect(File::glob($backupPath.'/manifest-*.json'))
            ->sortByDesc(fn (string $path): int => filemtime($path) ?: 0)
            ->first();

        if (! is_string($manifestPath)) {
            throw new RuntimeException('No local backup manifest is available for external replication.');
        }

        return $this->fromManifest($manifestPath);
    }

    public function fromDirectory(string $backupPath): array
    {
        return $this->latest($backupPath);
    }

    private function fromManifest(string $manifestPath): array
    {
        try {
            $manifest = json_decode((string) File::get($manifestPath), true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable $exception) {
            throw new RuntimeException('The local backup manifest is not valid JSON.', previous: $exception);
        }

        if (! is_array($manifest) || ! is_array($manifest['files'] ?? null)) {
            throw new RuntimeException('The local backup manifest does not describe any files.');
        }

        try {
            $createdAt = CarbonImmutable::parse((string) ($manifest['created_at'] ?? ''));
        } catch (\Throwable $exception) {
            throw new RuntimeException('The local backup manifest has an invalid creation time.', previous: $exception);
        }

        $directory = dirname($manifestPath);
        $files = [];

        foreach ($manifest['files'] as $file) {
            if (! is_array($file)) {
                throw new RuntimeException('The local backup manifest contains an invalid file entry.');
            }

            $type = (string) ($file['type'] ?? '');
            $name = basename((string) ($file['path'] ?? ''));

            if (! in_array($type, ['database', 'storage_app'], true) || $name === '') {
                throw new RuntimeException('The local backup manifest contains an unsupported file entry.');
            }

            $expectedPattern = $type === 'database'
                ? '/^db-\d{8}-\d{6}\.sql\.gz$/'
                : '/^storage-app-\d{8}-\d{6}\.tar\.gz$/';

            if (preg_match($expectedPattern, $name) !== 1) {
                throw new RuntimeException('The local backup manifest contains an unsafe file name.');
            }

            $path = $directory.'/'.$name;

            if (! is_file($path) || ! is_readable($path)) {
                throw new RuntimeException('A file referenced by the local backup manifest is missing.');
            }

            $expectedSize = (int) ($file['size_bytes'] ?? -1);
            $expectedHash = strtolower((string) ($file['sha256'] ?? ''));
            $actualSize = (int) filesize($path);
            $actualHash = hash_file('sha256', $path);

            if ($expectedSize !== $actualSize || ! hash_equals($expectedHash, $actualHash)) {
                throw new RuntimeException('A local backup file failed its manifest integrity check.');
            }

            $files[$type] = [
                'type' => $type,
                'name' => $name,
                'path' => $path,
                'size_bytes' => $actualSize,
                'sha256' => $actualHash,
            ];
        }

        if (! isset($files['database'], $files['storage_app'])) {
            throw new RuntimeException('The local backup set must contain both database and private storage archives.');
        }

        return [
            'id' => $createdAt->format('Ymd-His'),
            'created_at' => $createdAt,
            'directory' => $directory,
            'manifest_path' => $manifestPath,
            'manifest_name' => basename($manifestPath),
            'manifest_sha256' => hash_file('sha256', $manifestPath),
            'files' => $files,
        ];
    }
}
