<?php

namespace App\Services;

use App\Jobs\DeleteStoredFile;
use App\Support\StoredFileReference;
use App\Support\StoredFileSwapResult;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use LogicException;
use RuntimeException;
use Throwable;

class StoredFileReplacementService
{
    /**
     * Store and verify a new file before atomically swapping its database reference.
     *
     * The swap callback runs inside a database transaction and must return the value
     * requested by the caller together with the file reference it replaced. A failed
     * write or database swap removes only the newly staged file. The previous file is
     * removed only after the database transaction has committed successfully.
     */
    public function replace(
        string $disk,
        string $path,
        Closure $write,
        Closure $swap,
        ?int $expectedSize = null,
    ): mixed {
        $newFile = new StoredFileReference($disk, $path, $expectedSize);
        $storage = Storage::disk($disk);

        try {
            $writeResult = $write();

            if ($writeResult === false || (is_string($writeResult) && $writeResult !== $path)) {
                throw new RuntimeException('The new file could not be stored at the expected location.');
            }

            if (! $storage->exists($path)) {
                throw new RuntimeException('The new file was not found after it was stored.');
            }

            $storedSize = $storage->size($path);
            if ($expectedSize !== null && $storedSize !== $expectedSize) {
                throw new RuntimeException('The stored file size does not match the uploaded file.');
            }

            $newFile = new StoredFileReference($disk, $path, $storedSize);

            if (DB::transactionLevel() > 0) {
                DB::connection()->afterRollBack(
                    fn (): bool => $this->deleteQuietly($newFile, 'new file after a surrounding transaction rollback'),
                );
            }

            $result = DB::transaction(function () use ($swap, $newFile): StoredFileSwapResult {
                $result = $swap($newFile);

                if (! $result instanceof StoredFileSwapResult) {
                    throw new LogicException('The file swap callback must return a StoredFileSwapResult.');
                }

                return $result;
            });
        } catch (Throwable $exception) {
            $this->deleteQuietly($newFile, 'new file after a failed replacement');

            throw $exception;
        }

        $this->deleteReplacedFileAfterCommit($result->replacedFile, $newFile);

        return $result->value;
    }

    /**
     * Atomically remove a database reference before deleting its stored file.
     */
    public function remove(Closure $swap): mixed
    {
        $result = DB::transaction(function () use ($swap): StoredFileSwapResult {
            $result = $swap();

            if (! $result instanceof StoredFileSwapResult) {
                throw new LogicException('The file removal callback must return a StoredFileSwapResult.');
            }

            return $result;
        });

        $this->deleteReplacedFileAfterCommit($result->replacedFile);

        return $result->value;
    }

    private function deleteReplacedFileAfterCommit(
        ?StoredFileReference $replacedFile,
        ?StoredFileReference $newFile = null,
    ): void {
        if ($replacedFile === null || ($newFile && $replacedFile->isSameLocationAs($newFile))) {
            return;
        }

        $delete = function () use ($replacedFile): void {
            if (! $this->deleteQuietly($replacedFile, 'replaced file after commit')) {
                DeleteStoredFile::dispatch($replacedFile->disk, $replacedFile->path);
            }
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($delete);

            return;
        }

        $delete();
    }

    private function deleteQuietly(StoredFileReference $file, string $context): bool
    {
        try {
            $storage = Storage::disk($file->disk);

            if (! $storage->exists($file->path)) {
                return true;
            }

            if ($storage->delete($file->path)) {
                return true;
            }
        } catch (Throwable $exception) {
            Log::warning('Stored file cleanup failed.', [
                'context' => $context,
                'disk' => $file->disk,
                'path' => $file->path,
                'exception' => $exception->getMessage(),
            ]);

            return false;
        }

        Log::warning('Stored file cleanup failed.', [
            'context' => $context,
            'disk' => $file->disk,
            'path' => $file->path,
        ]);

        return false;
    }
}
