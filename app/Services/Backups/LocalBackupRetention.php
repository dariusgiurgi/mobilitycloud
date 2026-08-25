<?php

namespace App\Services\Backups;

use Illuminate\Support\Facades\File;

class LocalBackupRetention
{
    private const MANAGED_FILE_PATTERNS = [
        '/^db-\d{8}-\d{6}\.sql\.gz$/',
        '/^storage-app-\d{8}-\d{6}\.tar\.gz$/',
        '/^manifest-\d{8}-\d{6}\.json$/',
    ];

    public function purge(string $backupPath, int $retentionDays): int
    {
        if ($retentionDays < 1 || ! is_dir($backupPath)) {
            return 0;
        }

        $threshold = now()->subDays($retentionDays)->getTimestamp();
        $deleted = 0;

        foreach (File::files($backupPath) as $file) {
            if (! $this->isManagedBackupFile($file->getFilename()) || $file->getMTime() >= $threshold) {
                continue;
            }

            if (File::delete($file->getPathname())) {
                $deleted++;
            }
        }

        return $deleted;
    }

    private function isManagedBackupFile(string $filename): bool
    {
        foreach (self::MANAGED_FILE_PATTERNS as $pattern) {
            if (preg_match($pattern, $filename) === 1) {
                return true;
            }
        }

        return false;
    }
}
