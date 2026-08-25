<?php

namespace App\Services\Backups;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

class ExternalBackupRestoreService
{
    public function __construct(
        private readonly BackupStreamCipher $cipher,
        private readonly LocalBackupSetLocator $locator,
    ) {}

    public function verify(callable $databaseVerifier): array
    {
        $diskName = (string) config('mobilitycloud.external_backups.disk');
        $prefix = trim((string) config('mobilitycloud.external_backups.prefix', 'mobilitycloud'), '/');
        $keyPath = (string) config('mobilitycloud.external_backups.key_path');
        $disk = Storage::disk($diskName);
        $metadata = $this->latestMetadata($disk, $prefix);
        $workingPath = storage_path('app/private/backup-restore-work/'.Str::uuid());
        File::ensureDirectoryExists($workingPath, 0700, true);
        $encryptedPath = $workingPath.'/snapshot.mcb';
        $bundlePath = $workingPath.'/snapshot.tar';
        $extractPath = $workingPath.'/extracted';
        File::ensureDirectoryExists($extractPath, 0700, true);

        try {
            $this->download($disk, (string) $metadata['object_path'], $encryptedPath);
            $this->verifyDownloadedObject($encryptedPath, $metadata);
            $this->cipher->decrypt($encryptedPath, $bundlePath, $keyPath);
            $this->extractBundle($bundlePath, $extractPath);
            $set = $this->locator->fromDirectory($extractPath);

            if (! hash_equals((string) $metadata['local_manifest_sha256'], $set['manifest_sha256'])) {
                throw new RuntimeException('The restored manifest does not match the external snapshot metadata.');
            }

            $storageEntries = $this->verifyStorageArchive($set['files']['storage_app']['path']);
            $databaseResult = $databaseVerifier($extractPath, $set);

            if (! is_array($databaseResult)) {
                throw new RuntimeException('The database restore verifier did not return a valid result.');
            }

            return [
                'status' => 'ok',
                'backup_id' => (string) $metadata['backup_id'],
                'backup_created_at' => (string) $metadata['created_at'],
                'verified_at' => now()->toISOString(),
                'object_path' => (string) $metadata['object_path'],
                'size_bytes' => (int) $metadata['size_bytes'],
                'sha256' => (string) $metadata['sha256'],
                'key_id' => (string) $metadata['key_id'],
                'storage_entries' => $storageEntries,
                'database' => $databaseResult,
            ];
        } finally {
            if (str_starts_with($workingPath, storage_path('app/private/backup-restore-work/'))) {
                File::deleteDirectory($workingPath);
            }
        }
    }

    private function latestMetadata(FilesystemAdapter $disk, string $prefix): array
    {
        $paths = collect($disk->allFiles($prefix.'/metadata'))
            ->filter(fn (string $path): bool => str_ends_with($path, '.json'))
            ->sortDesc();

        foreach ($paths as $path) {
            try {
                $metadata = json_decode($disk->get($path), true, flags: JSON_THROW_ON_ERROR);
            } catch (\Throwable) {
                continue;
            }

            if ($this->validMetadata($metadata, $prefix) && $disk->exists((string) $metadata['object_path'])) {
                return $metadata;
            }
        }

        throw new RuntimeException('No valid external backup snapshot is available for restore testing.');
    }

    private function validMetadata(mixed $metadata, string $prefix): bool
    {
        if (! is_array($metadata)) {
            return false;
        }

        $objectPath = (string) ($metadata['object_path'] ?? '');

        return ($metadata['schema'] ?? null) === 1
            && ($metadata['status'] ?? null) === 'ok'
            && filled($metadata['backup_id'] ?? null)
            && filled($metadata['created_at'] ?? null)
            && str_starts_with($objectPath, $prefix.'/snapshots/')
            && str_ends_with($objectPath, '.mcb')
            && (int) ($metadata['size_bytes'] ?? 0) > 0
            && preg_match('/^[a-f0-9]{64}$/', (string) ($metadata['sha256'] ?? '')) === 1
            && preg_match('/^[a-f0-9]{64}$/', (string) ($metadata['local_manifest_sha256'] ?? '')) === 1;
    }

    private function download(FilesystemAdapter $disk, string $objectPath, string $targetPath): void
    {
        $source = $disk->readStream($objectPath);

        if ($source === false) {
            throw new RuntimeException('The external backup could not be opened for restore testing.');
        }

        $target = fopen($targetPath, 'wb');

        if ($target === false) {
            fclose($source);

            throw new RuntimeException('The external backup restore workspace is not writable.');
        }

        try {
            if (stream_copy_to_stream($source, $target) === false) {
                throw new RuntimeException('The external backup download did not complete.');
            }
        } finally {
            fclose($source);
            fclose($target);
        }

        @chmod($targetPath, 0600);
    }

    private function verifyDownloadedObject(string $path, array $metadata): void
    {
        if ((int) filesize($path) !== (int) $metadata['size_bytes']) {
            throw new RuntimeException('The downloaded backup size does not match its metadata.');
        }

        if (! hash_equals((string) $metadata['sha256'], hash_file('sha256', $path))) {
            throw new RuntimeException('The downloaded backup failed its integrity check.');
        }
    }

    private function extractBundle(string $bundlePath, string $targetPath): void
    {
        [$entries, $lines] = $this->tarEntries($bundlePath);

        if (count($entries) !== 3) {
            throw new RuntimeException('The external backup bundle does not contain the expected three files.');
        }

        foreach ($entries as $index => $entry) {
            $line = $lines[$index];

            if (! str_starts_with($line, '-')) {
                throw new RuntimeException('The external backup bundle contains a non-regular file.');
            }

            if ($entry !== basename($entry) || str_contains($entry, '..')) {
                throw new RuntimeException('The external backup bundle contains an unsafe path.');
            }
        }

        $extract = new Process(['tar', '--no-same-owner', '--no-same-permissions', '-xf', $bundlePath, '-C', $targetPath]);
        $extract->setTimeout(600);
        $extract->mustRun();
    }

    private function verifyStorageArchive(string $storagePath): int
    {
        [$entries, $lines] = $this->tarEntries($storagePath, compressed: true);

        if ($entries === []) {
            throw new RuntimeException('The restored private storage archive is empty.');
        }

        foreach ($entries as $index => $entry) {
            $line = $lines[$index];

            if (! str_starts_with($line, '-') && ! str_starts_with($line, 'd')) {
                throw new RuntimeException('The restored private storage archive contains a non-regular entry.');
            }

            $trimmed = rtrim($entry, '/');

            if (($trimmed !== 'app' && ! str_starts_with($trimmed, 'app/'))
                || str_starts_with($trimmed, '/')
                || str_contains('/'.$trimmed.'/', '/../')) {
                throw new RuntimeException('The restored private storage archive contains an unsafe path.');
            }
        }

        return count($entries);
    }

    private function tarEntries(string $archivePath, bool $compressed = false): array
    {
        $names = new Process(['tar', $compressed ? '-tzf' : '-tf', $archivePath]);
        $names->setTimeout(3600);
        $names->mustRun();
        $entries = array_values(array_filter(preg_split('/\R/', trim($names->getOutput())) ?: []));

        $verbose = new Process(['tar', $compressed ? '-tvzf' : '-tvf', $archivePath]);
        $verbose->setTimeout(3600);
        $verbose->mustRun();
        $lines = array_values(array_filter(preg_split('/\R/', trim($verbose->getOutput())) ?: []));

        if (count($entries) !== count($lines)) {
            throw new RuntimeException('The backup archive listing is inconsistent.');
        }

        return [$entries, $lines];
    }
}
