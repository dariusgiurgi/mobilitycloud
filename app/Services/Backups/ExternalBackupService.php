<?php

namespace App\Services\Backups;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

class ExternalBackupService
{
    public function __construct(
        private readonly BackupStreamCipher $cipher,
        private readonly LocalBackupSetLocator $locator,
    ) {}

    public function replicate(?string $backupPath = null): array
    {
        $backupPath ??= (string) config('mobilitycloud.backups.path');
        $set = $this->locator->latest($backupPath);
        $diskName = (string) config('mobilitycloud.external_backups.disk');
        $prefix = trim((string) config('mobilitycloud.external_backups.prefix', 'mobilitycloud'), '/');
        $keyPath = (string) config('mobilitycloud.external_backups.key_path');
        $disk = Storage::disk($diskName);
        $createdAt = $set['created_at'];
        $baseName = 'mobilitycloud-'.$set['id'];
        $objectPath = $prefix.'/snapshots/'.$createdAt->format('Y/m').'/'.$baseName.'.mcb';
        $metadataPath = $prefix.'/metadata/'.$createdAt->format('Y/m').'/'.$baseName.'.json';

        $existing = $this->existingMetadata($disk, $metadataPath, $set);

        if ($existing !== null) {
            return $existing + ['already_present' => true];
        }

        if ($disk->exists($objectPath)) {
            $suffix = now()->format('His');
            $objectPath = $prefix.'/snapshots/'.$createdAt->format('Y/m').'/'.$baseName.'-retry-'.$suffix.'.mcb';
            $metadataPath = $prefix.'/metadata/'.$createdAt->format('Y/m').'/'.$baseName.'-retry-'.$suffix.'.json';
        }

        $workingPath = storage_path('app/private/backup-work/'.Str::uuid());
        File::ensureDirectoryExists($workingPath, 0700, true);
        $plainBundle = $workingPath.'/'.$baseName.'.tar';
        $encryptedBundle = $workingPath.'/'.$baseName.'.mcb';

        try {
            $this->createBundle($set, $plainBundle);
            $encrypted = $this->cipher->encrypt($plainBundle, $encryptedBundle, $keyPath);
            $this->upload($disk, $objectPath, $encryptedBundle);
            $this->verifyRemoteObject($disk, $objectPath, $encrypted);

            $metadata = [
                'schema' => 1,
                'status' => 'ok',
                'backup_id' => $set['id'],
                'created_at' => $createdAt->toISOString(),
                'uploaded_at' => now()->toISOString(),
                'object_path' => $objectPath,
                'metadata_path' => $metadataPath,
                'size_bytes' => $encrypted['size_bytes'],
                'sha256' => $encrypted['sha256'],
                'key_id' => $encrypted['key_id'],
                'encryption_format' => $encrypted['format'],
                'local_manifest_sha256' => $set['manifest_sha256'],
                'verified_after_upload' => true,
            ];

            $disk->put(
                $metadataPath,
                json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL,
                ['visibility' => 'private'],
            );

            if (! $disk->exists($metadataPath)) {
                throw new RuntimeException('The external backup metadata could not be confirmed after upload.');
            }

            return $metadata + ['already_present' => false];
        } finally {
            if (str_starts_with($workingPath, storage_path('app/private/backup-work/'))) {
                File::deleteDirectory($workingPath);
            }
        }
    }

    private function createBundle(array $set, string $targetPath): void
    {
        $names = [
            $set['manifest_name'],
            $set['files']['database']['name'],
            $set['files']['storage_app']['name'],
        ];

        $process = new Process(array_merge(
            ['tar', '-cf', $targetPath, '-C', $set['directory'], '--'],
            $names,
        ));
        $process->setTimeout(3600);
        $process->mustRun();
        @chmod($targetPath, 0600);
    }

    private function upload(FilesystemAdapter $disk, string $objectPath, string $sourcePath): void
    {
        $stream = fopen($sourcePath, 'rb');

        if ($stream === false) {
            throw new RuntimeException('Could not open the encrypted backup for upload.');
        }

        try {
            $written = $disk->writeStream($objectPath, $stream, [
                'visibility' => 'private',
                'ContentType' => 'application/octet-stream',
            ]);
        } finally {
            fclose($stream);
        }

        if (! $written) {
            throw new RuntimeException('The external backup provider rejected the upload.');
        }
    }

    private function verifyRemoteObject(FilesystemAdapter $disk, string $objectPath, array $expected): void
    {
        if (! $disk->exists($objectPath) || $disk->size($objectPath) !== $expected['size_bytes']) {
            throw new RuntimeException('The external backup size does not match after upload.');
        }

        $stream = $disk->readStream($objectPath);

        if ($stream === false) {
            throw new RuntimeException('The uploaded external backup could not be read for verification.');
        }

        try {
            $hash = hash_init('sha256');
            hash_update_stream($hash, $stream);
            $actualHash = hash_final($hash);
        } finally {
            fclose($stream);
        }

        if (! hash_equals($expected['sha256'], $actualHash)) {
            throw new RuntimeException('The external backup failed its post-upload integrity check.');
        }
    }

    private function existingMetadata(FilesystemAdapter $disk, string $metadataPath, array $set): ?array
    {
        if (! $disk->exists($metadataPath)) {
            return null;
        }

        try {
            $metadata = json_decode($disk->get($metadataPath), true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }

        if (! is_array($metadata)
            || ($metadata['backup_id'] ?? null) !== $set['id']
            || ! hash_equals((string) ($metadata['local_manifest_sha256'] ?? ''), $set['manifest_sha256'])
            || ! filled($metadata['object_path'] ?? null)
            || ! $disk->exists((string) $metadata['object_path'])) {
            return null;
        }

        return $metadata;
    }
}
