<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use App\Support\StoredFileReference;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class StoredFilePurgeService
{
    public function purgeProject(Project $project): void
    {
        $files = collect();
        $this->push($files, $project->approved_grant_proof_disk, $project->approved_grant_proof_path);

        $project->documents()->get()->each(function ($document) use ($files): void {
            $this->push($files, $document->file_disk, $document->file_path);
            $this->push($files, $document->signed_disk, $document->signed_path);
        });

        $project->participants()->with('attachments')->get()->each(function ($participant) use ($files): void {
            $participant->attachments->each(fn ($attachment) => $this->push($files, $attachment->disk, $attachment->path));
        });

        $project->budgetLines()->with(['expenses' => fn ($query) => $query->withTrashed()])->get()
            ->flatMap->expenses
            ->each(function ($expense) use ($files): void {
                $this->push($files, $expense->attachment_disk, $expense->attachment_path);

                foreach (['agreement', 'acceptance', 'payment'] as $kind) {
                    $copy = $expense->conventionSignedCopy($kind);
                    $this->push($files, $copy['disk'], $copy['path']);
                }
            });

        $project->finalArchives()->get()
            ->each(fn ($archive) => $this->push($files, $archive->disk, $archive->path));

        $this->purge($files);
    }

    public function purgeAccountBranding(User $account): void
    {
        $files = collect();
        $this->push($files, 'local', data_get($account->document_settings, 'logo_path'));
        $this->purge($files);
    }

    /** @param Collection<int, StoredFileReference> $files */
    private function purge(Collection $files): void
    {
        $files
            ->unique(fn (StoredFileReference $file): string => $file->disk."\0".$file->path)
            ->each(function (StoredFileReference $file): void {
                $storage = Storage::disk($file->disk);

                if (! $storage->exists($file->path)) {
                    return;
                }

                if (! $storage->delete($file->path) || $storage->exists($file->path)) {
                    throw new RuntimeException('Could not purge stored file '.$file->disk.':'.$file->path.'.');
                }
            });
    }

    private function push(Collection $files, ?string $disk, mixed $path): void
    {
        $reference = StoredFileReference::from($disk, is_string($path) ? $path : null);

        if ($reference) {
            $files->push($reference);
        }
    }
}
