<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\ProjectDocument;
use App\Support\AuthorizesProjectManagement;
use App\Support\GeneratesAttendanceSheets;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;

class ViewProjectDocuments extends Page
{
    use AuthorizesProjectManagement;
    use GeneratesAttendanceSheets;
    use InteractsWithRecord;
    use WithFileUploads;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.pages.view-project-documents';

    public bool $showSignedUploadModal = false;

    public ?int $signedDocumentId = null;

    public $signedUpload = null;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string
    {
        return $this->record->name.' - Documents';
    }

    public function getDocuments()
    {
        return ProjectDocument::where('project_id', $this->record->id)
            ->latest('activity_date')
            ->latest('id')
            ->get();
    }

    public function openSignedUpload(int $documentId): void
    {
        $this->authorizeProjectManagement();
        $document = $this->findDocument($documentId);
        if (! $document) {
            return;
        }

        $this->signedDocumentId = $document->id;
        $this->signedUpload = null;
        $this->showSignedUploadModal = true;
    }

    public function closeSignedUpload(): void
    {
        $this->showSignedUploadModal = false;
        $this->signedDocumentId = null;
        $this->signedUpload = null;
    }

    public function uploadSignedCopy(): void
    {
        $this->authorizeProjectManagement();
        $this->validate([
            'signedUpload' => 'required|file|max:20480|mimes:pdf,jpg,jpeg,png',
        ]);

        $document = $this->findDocument($this->signedDocumentId);
        if (! $document) {
            $this->closeSignedUpload();

            return;
        }

        if ($document->hasSignedCopy()) {
            Storage::disk($document->signed_disk ?: 'local')->delete($document->signed_path);
        }

        $extension = strtolower($this->signedUpload->getClientOriginalExtension() ?: 'pdf');
        $filename = 'signed_'.Str::slug($document->title).'_'.$document->id.'.'.$extension;
        $path = $this->signedUpload->storeAs(
            'project-documents/'.$this->record->id.'/'.$document->id,
            $filename,
            'local'
        );

        $document->update([
            'signed_path' => $path,
            'signed_disk' => 'local',
            'signed_name' => $this->signedUpload->getClientOriginalName(),
            'signed_size' => $this->signedUpload->getSize(),
            'signed_at' => now(),
        ]);

        $this->closeSignedUpload();
        Notification::make()->title('Signed copy uploaded')->success()->send();
    }

    public function deleteSignedCopy(int $documentId): void
    {
        $this->authorizeProjectManagement();
        $document = $this->findDocument($documentId);
        if (! $document) {
            return;
        }

        if ($document->hasSignedCopy()) {
            Storage::disk($document->signed_disk ?: 'local')->delete($document->signed_path);
        }

        $document->update([
            'signed_path' => null,
            'signed_name' => null,
            'signed_size' => 0,
            'signed_at' => null,
        ]);

        Notification::make()->title('Signed copy removed')->success()->send();
    }

    public function deleteDocument(int $documentId): void
    {
        $this->authorizeProjectManagement();
        $document = $this->findDocument($documentId);
        $document?->delete();
        Notification::make()->title('Document removed')->success()->send();
    }

    private function findDocument(?int $documentId): ?ProjectDocument
    {
        if (! $documentId) {
            return null;
        }

        return ProjectDocument::where('project_id', $this->record->id)->find($documentId);
    }
}
