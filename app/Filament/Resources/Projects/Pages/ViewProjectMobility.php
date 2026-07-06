<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\ProjectDocument;
use App\Support\AuthorizesProjectManagement;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;

class ViewProjectMobility extends Page
{
    use AuthorizesProjectManagement;
    use InteractsWithRecord;
    use WithFileUploads;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.pages.view-project-mobility';

    public string $mobilityReport = '';

    public string $documentTitle = '';

    public string $documentCategory = 'mobility_material';

    public ?string $documentDate = null;

    public string $documentNotes = '';

    public $documentUpload = null;

    public string $documentSearch = '';

    public string $categoryFilter = '';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        ProjectResource::ensureProjectAccountTenant($this->record, 'mobility');
        $this->mobilityReport = (string) data_get($this->record->action_data ?? [], 'mobility.report', '');
        $this->documentDate = now()->toDateString();
    }

    public function getTitle(): string
    {
        return $this->record->name.' - Mobility';
    }

    public function getMobilityCategories(): array
    {
        return ProjectDocument::MOBILITY_CATEGORIES;
    }

    public function getMobilityDocuments()
    {
        return $this->record->documents()
            ->where('type', ProjectDocument::TYPE_UPLOAD)
            ->whereIn('category', array_keys(ProjectDocument::MOBILITY_CATEGORIES))
            ->when(filled($this->categoryFilter), fn ($query) => $query->where('category', $this->categoryFilter))
            ->when(filled($this->documentSearch), function ($query): void {
                $search = '%'.trim($this->documentSearch).'%';
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', $search)
                        ->orWhere('file_name', 'like', $search)
                        ->orWhere('notes', 'like', $search);
                });
            })
            ->orderByRaw('document_date is null')
            ->orderBy('document_date')
            ->orderBy('category')
            ->orderBy('title')
            ->get();
    }

    public function getMobilitySummary(): array
    {
        $documents = $this->record->documents()
            ->where('type', ProjectDocument::TYPE_UPLOAD)
            ->whereIn('category', array_keys(ProjectDocument::MOBILITY_CATEGORIES))
            ->get();

        return [
            'files' => $documents->count(),
            'plans' => $documents->where('category', 'mobility_plan')->count(),
            'materials' => $documents->where('category', 'mobility_material')->count(),
            'outputs' => $documents->where('category', 'mobility_output')->count(),
            'evidence' => $documents->whereIn('category', ['mobility_photo_video', 'mobility_other'])->count(),
            'report_ready' => filled(trim($this->mobilityReport)),
        ];
    }

    public function saveMobilityReport(): void
    {
        $this->authorizeProjectManagement();
        $this->validate([
            'mobilityReport' => ['nullable', 'string', 'max:12000'],
        ]);

        $data = $this->record->action_data ?? [];
        data_set($data, 'mobility.report', trim($this->mobilityReport));
        data_set($data, 'mobility.report_updated_at', now()->toIso8601String());
        data_set($data, 'mobility.report_updated_by', auth()->id());

        $this->record->update(['action_data' => $data]);
        $this->record = $this->record->fresh();

        Notification::make()->title('Mobility report saved')->success()->send();
    }

    public function uploadMobilityDocument(): void
    {
        $this->authorizeProjectManagement();
        $this->validate([
            'documentTitle' => ['required', 'string', 'max:255'],
            'documentCategory' => ['required', 'in:'.implode(',', array_keys(ProjectDocument::MOBILITY_CATEGORIES))],
            'documentDate' => ['nullable', 'date'],
            'documentNotes' => ['nullable', 'string', 'max:3000'],
            'documentUpload' => ['required', 'file', 'max:51200', 'mimes:pdf,jpg,jpeg,png,gif,webp,doc,docx,xls,xlsx,ppt,pptx,zip'],
        ]);

        $document = $this->record->documents()->create([
            'type' => ProjectDocument::TYPE_UPLOAD,
            'category' => $this->documentCategory,
            'title' => trim($this->documentTitle),
            'document_date' => $this->documentDate ?: null,
            'notes' => trim($this->documentNotes) ?: null,
            'metadata' => [
                'source' => 'mobility',
                'uploaded_from' => 'mobility_page',
            ],
        ]);

        try {
            $extension = strtolower($this->documentUpload->getClientOriginalExtension() ?: 'dat');
            $filename = Str::slug($document->title).'_'.$document->id.'.'.$extension;
            $path = $this->documentUpload->storeAs(
                'project-documents/'.$this->record->id.'/mobility/'.$this->documentCategory,
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

        $this->reset('documentTitle', 'documentNotes', 'documentUpload');
        $this->documentCategory = 'mobility_material';
        $this->documentDate = now()->toDateString();

        Notification::make()->title('Mobility document uploaded')->success()->send();
    }

    public function deleteMobilityDocument(int $documentId): void
    {
        $this->authorizeProjectManagement();
        $document = $this->record->documents()
            ->where('type', ProjectDocument::TYPE_UPLOAD)
            ->whereIn('category', array_keys(ProjectDocument::MOBILITY_CATEGORIES))
            ->find($documentId);

        if (! $document) {
            return;
        }

        $document->delete();
        Notification::make()->title('Mobility document removed')->success()->send();
    }
}
