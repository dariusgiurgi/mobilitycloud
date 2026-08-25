<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class DeleteStoredFile implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 6;

    public int $uniqueFor = 86400;

    public function __construct(
        public string $disk,
        public string $path,
    ) {
        $this->onQueue('default');
    }

    public function uniqueId(): string
    {
        return hash('sha256', $this->disk."\0".$this->path);
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [30, 120, 600, 1800, 3600];
    }

    public function handle(): void
    {
        $storage = Storage::disk($this->disk);

        if (! $storage->exists($this->path)) {
            return;
        }

        if (! $storage->delete($this->path) || $storage->exists($this->path)) {
            throw new RuntimeException('The obsolete stored file could not be deleted.');
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Obsolete stored file cleanup exhausted all retries.', [
            'disk' => $this->disk,
            'path' => $this->path,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
