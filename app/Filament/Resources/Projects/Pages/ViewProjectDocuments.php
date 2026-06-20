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

    public bool $showDocumentUploadModal = false;

    public string $documentTitle = '';

    public string $documentCategory = 'other';

    public ?string $documentDate = null;

    public string $documentNotes = '';

    public $documentUpload = null;

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
            ->latest('id')
            ->get();
    }

    public function getDocumentCategories(): array
    {
        return ProjectDocument::CATEGORIES;
    }

    public function openDocumentUpload(): void
    {
        $this->authorizeProjectManagement();
        $this->resetValidation();
        $this->documentTitle = '';
        $this->documentCategory = 'other';
        $this->documentDate = null;
        $this->documentNotes = '';
        $this->documentUpload = null;
        $this->showDocumentUploadModal = true;
    }

    public function closeDocumentUpload(): void
    {
        $this->showDocumentUploadModal = false;
        $this->documentUpload = null;
    }

    public function uploadProjectDocument(): void
    {
        $this->authorizeProjectManagement();
        $this->validate([
            'documentTitle' => ['required', 'string', 'max:255'],
            'documentCategory' => ['required', 'in:'.implode(',', array_keys(ProjectDocument::CATEGORIES))],
            'documentDate' => ['nullable', 'date'],
            'documentNotes' => ['nullable', 'string', 'max:2000'],
            'documentUpload' => ['required', 'file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx'],
        ]);

        $document = $this->record->documents()->create([
            'type' => ProjectDocument::TYPE_UPLOAD,
            'category' => $this->documentCategory,
            'title' => trim($this->documentTitle),
            'document_date' => $this->documentDate ?: null,
            'notes' => trim($this->documentNotes) ?: null,
        ]);

        try {
            $extension = strtolower($this->documentUpload->getClientOriginalExtension() ?: 'dat');
            $filename = Str::slug($document->title).'_'.$document->id.'.'.$extension;
            $path = $this->documentUpload->storeAs(
                'project-documents/'.$this->record->id.'/'.$document->id,
                $filename,
                'local'
            );

            $document->update([
                'file_path' => $path,
                'file_disk' => 'local',
                'file_name' => $this->documentUpload->getClientOriginalName(),
                'file_size' => $this->documentUpload->getSize(),
            ]);
        } catch (\Throwable $exception) {
            $document->delete();
            throw $exception;
        }

        $this->closeDocumentUpload();
        Notification::make()->title('Project document uploaded')->success()->send();
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
