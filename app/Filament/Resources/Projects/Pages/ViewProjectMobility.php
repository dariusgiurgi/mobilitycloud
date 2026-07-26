<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\ProjectDocument;
use App\Support\AuthorizesProjectManagement;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
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

    public string $photoFolderUrl = '';

    public string $photoEvidenceTitle = 'Mobility photo evidence';

    public ?string $photoEvidenceDate = null;

    public string $photoEvidenceNotes = '';

    public array $photoUploads = [];

    public string $documentTitle = '';

    public string $documentCategory = 'mobility_material';

    public ?string $documentDate = null;

    public string $documentNotes = '';

    public $documentUpload = null;

    public string $documentSearch = '';

    public string $categoryFilter = '';

    public array $disseminationReports = [];

    public string $disseminationUploadTitle = '';

    public ?string $disseminationUploadDate = null;

    public array $disseminationUploads = [];

    public ?string $disseminationUploadOrgKey = null;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        ProjectResource::ensureProjectAccountTenant($this->record, 'mobility');
        $this->authorizeProjectAccess();
        $this->mobilityReport = (string) data_get($this->record->action_data ?? [], 'mobility.report', '');
        $this->photoFolderUrl = (string) data_get($this->record->action_data ?? [], 'mobility.photo_folder_url', '');
        $this->documentDate = now()->toDateString();
        $this->photoEvidenceDate = now()->toDateString();
        $this->disseminationUploadDate = now()->toDateString();
        $this->disseminationReports = $this->storedDisseminationReports();
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
            'photo_folder_ready' => filled(trim($this->photoFolderUrl)),
            'dissemination' => $this->getDisseminationSummary(),
        ];
    }

    public function savePhotoFolderUrl(): void
    {
        $this->authorizeManagementModuleMutation();
        $this->validate([
            'photoFolderUrl' => ['nullable', 'url', 'max:2000'],
        ]);

        $data = $this->record->action_data ?? [];
        data_set($data, 'mobility.photo_folder_url', trim($this->photoFolderUrl));
        data_set($data, 'mobility.photo_folder_updated_at', now()->toIso8601String());
        data_set($data, 'mobility.photo_folder_updated_by', auth()->id());

        $this->record->update(['action_data' => $data]);
        $this->record = $this->record->fresh();
        $this->photoFolderUrl = (string) data_get($this->record->action_data ?? [], 'mobility.photo_folder_url', '');

        Notification::make()->title('Photo folder link saved')->success()->send();
    }

    public function uploadMobilityPhotos(): void
    {
        $this->authorizeManagementModuleMutation();
        $this->validate([
            'photoEvidenceTitle' => ['required', 'string', 'max:255'],
            'photoEvidenceDate' => ['nullable', 'date'],
            'photoEvidenceNotes' => ['nullable', 'string', 'max:3000'],
            'photoUploads' => ['required', 'array', 'max:20'],
            'photoUploads.*' => ['file', 'max:8192', 'mimes:jpg,jpeg,png,webp'],
        ]);

        foreach ($this->photoUploads as $upload) {
            $document = $this->record->documents()->create([
                'type' => ProjectDocument::TYPE_UPLOAD,
                'category' => 'mobility_photo_video',
                'title' => trim($this->photoEvidenceTitle),
                'document_date' => $this->photoEvidenceDate ?: null,
                'notes' => trim($this->photoEvidenceNotes) ?: null,
                'metadata' => [
                    'source' => 'mobility',
                    'uploaded_from' => 'mobility_photo_batch',
                    'batch_title' => trim($this->photoEvidenceTitle),
                ],
            ]);

            try {
                $extension = strtolower($upload->getClientOriginalExtension() ?: 'jpg');
                $filename = Str::slug($document->title).'_'.$document->id.'.'.$extension;
                $path = $upload->storeAs(
                    'project-documents/'.$this->record->id.'/mobility/photos',
                    $filename,
                    'local'
                );

                $document->update([
                    'file_path' => $path,
                    'file_disk' => 'local',
                    'file_name' => $upload->getClientOriginalName(),
                    'file_size' => $upload->getSize(),
                ]);
            } catch (\Throwable $exception) {
                $document->delete();
                throw $exception;
            }
        }

        $count = count($this->photoUploads);
        $this->reset('photoUploads', 'photoEvidenceNotes');
        $this->photoEvidenceTitle = 'Mobility photo evidence';
        $this->photoEvidenceDate = now()->toDateString();

        Notification::make()->title($count.' mobility photo'.($count === 1 ? '' : 's').' uploaded')->success()->send();
    }

    public function saveMobilityReport(): void
    {
        $this->authorizeManagementModuleMutation();
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
        $this->authorizeManagementModuleMutation();
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
        $this->authorizeManagementModuleMutation();
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

    public function getDisseminationOrganisations(): array
    {
        $partners = collect($this->record->partners)
            ->filter(fn (array $partner): bool => filled($partner['name'] ?? null))
            ->values();

        if ($partners->isEmpty()) {
            $owner = $this->record->owner();
            $settings = $owner?->document_settings ?? [];

            $partners = collect([[
                'name' => $settings['legal_name']
                    ?? $settings['brand_name']
                    ?? $owner?->name
                    ?? 'Coordinator organisation',
                'country' => null,
                'oid' => null,
                'is_coordinator' => true,
            ]]);
        }

        return $partners
            ->map(function (array $partner, int $index): array {
                $name = trim((string) ($partner['name'] ?? 'Organisation '.($index + 1)));

                return [
                    'key' => $this->disseminationOrganisationKey($partner, $index),
                    'name' => $name,
                    'country' => filled($partner['country'] ?? null) ? trim((string) $partner['country']) : null,
                    'oid' => filled($partner['oid'] ?? null) ? trim((string) $partner['oid']) : null,
                    'is_coordinator' => (bool) ($partner['is_coordinator'] ?? false),
                ];
            })
            ->values()
            ->all();
    }

    public function getDisseminationEvidenceByOrganisation(): array
    {
        $documents = $this->record->documents()
            ->where('type', ProjectDocument::TYPE_UPLOAD)
            ->where('category', 'dissemination_evidence')
            ->latest('id')
            ->get();

        return collect($this->getDisseminationOrganisations())
            ->mapWithKeys(fn (array $organisation): array => [
                $organisation['key'] => $documents
                    ->filter(fn (ProjectDocument $document): bool => data_get($document->metadata, 'organisation_key') === $organisation['key'])
                    ->values(),
            ])
            ->all();
    }

    public function getDisseminationSummary(): array
    {
        $organisations = collect($this->getDisseminationOrganisations());
        $evidence = $this->getDisseminationEvidenceByOrganisation();
        $reports = $this->storedDisseminationReports();
        $withEvidence = $organisations
            ->filter(fn (array $organisation): bool => ($evidence[$organisation['key']] ?? collect())->isNotEmpty())
            ->count();
        $withReports = $organisations
            ->filter(fn (array $organisation): bool => filled(trim((string) ($reports[$organisation['key']] ?? ''))))
            ->count();

        return [
            'organisations' => $organisations->count(),
            'with_evidence' => $withEvidence,
            'with_reports' => $withReports,
            'complete' => $organisations->count() > 0
                && $withEvidence === $organisations->count()
                && $withReports === $organisations->count(),
            'missing' => max(0, ($organisations->count() * 2) - $withEvidence - $withReports),
        ];
    }

    public function saveDisseminationReport(string $organisationKey): void
    {
        $this->authorizeManagementModuleMutation();
        abort_unless(collect($this->getDisseminationOrganisations())->contains('key', $organisationKey), 404);

        $this->validate([
            'disseminationReports.'.$organisationKey => ['nullable', 'string', 'max:10000'],
        ]);

        $data = $this->record->action_data ?? [];
        $reports = data_get($data, 'dissemination_reports', []);
        $reports[$organisationKey] = trim((string) ($this->disseminationReports[$organisationKey] ?? ''));
        data_set($data, 'dissemination_reports', $reports);
        $this->record->update(['action_data' => $data]);
        $this->record = $this->record->fresh();
        $this->disseminationReports = $this->storedDisseminationReports();

        Notification::make()->title('Dissemination report saved')->success()->send();
    }

    public function prepareDisseminationUpload(string $organisationKey): void
    {
        $this->authorizeManagementModuleMutation();
        abort_unless(collect($this->getDisseminationOrganisations())->contains('key', $organisationKey), 404);

        $organisation = collect($this->getDisseminationOrganisations())->firstWhere('key', $organisationKey);
        $this->disseminationUploadOrgKey = $organisationKey;
        $this->disseminationUploadTitle = 'Dissemination evidence - '.$organisation['name'];
        $this->disseminationUploadDate = now()->toDateString();
        $this->disseminationUploads = [];
        $this->resetValidation('disseminationUploads');
    }

    public function uploadDisseminationEvidence(string $organisationKey): void
    {
        $this->authorizeManagementModuleMutation();
        abort_unless(collect($this->getDisseminationOrganisations())->contains('key', $organisationKey), 404);

        $this->disseminationUploadOrgKey = $organisationKey;
        $organisation = collect($this->getDisseminationOrganisations())->firstWhere('key', $organisationKey);

        $this->validate([
            'disseminationUploadTitle' => ['required', 'string', 'max:255'],
            'disseminationUploadDate' => ['nullable', 'date'],
            'disseminationUploads' => ['required', 'array', 'max:12'],
            'disseminationUploads.*' => ['file', 'max:12288', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx'],
        ]);

        foreach ($this->disseminationUploads as $upload) {
            $document = $this->record->documents()->create([
                'type' => ProjectDocument::TYPE_UPLOAD,
                'category' => 'dissemination_evidence',
                'title' => trim($this->disseminationUploadTitle),
                'document_date' => $this->disseminationUploadDate ?: now()->toDateString(),
                'notes' => trim((string) ($this->disseminationReports[$organisation['key']] ?? '')) ?: null,
                'metadata' => [
                    'source' => 'mobility',
                    'uploaded_from' => 'mobility_dissemination',
                    'organisation_key' => $organisation['key'],
                    'organisation_name' => $organisation['name'],
                    'organisation_country' => $organisation['country'],
                    'organisation_oid' => $organisation['oid'],
                ],
            ]);

            try {
                $extension = strtolower($upload->getClientOriginalExtension() ?: 'dat');
                $filename = Str::slug('dissemination-'.$organisation['name']).'_'.$document->id.'.'.$extension;
                $path = $upload->storeAs(
                    'project-documents/'.$this->record->id.'/dissemination/'.$organisation['key'],
                    $filename,
                    'local'
                );

                $document->update([
                    'file_path' => $path,
                    'file_disk' => 'local',
                    'file_name' => $upload->getClientOriginalName(),
                    'file_size' => $upload->getSize(),
                ]);
            } catch (\Throwable $exception) {
                $document->delete();
                throw $exception;
            }
        }

        $count = count($this->disseminationUploads);
        $this->disseminationUploads = [];
        $this->disseminationUploadTitle = '';
        $this->disseminationUploadDate = now()->toDateString();
        $this->disseminationUploadOrgKey = null;

        Notification::make()->title($count.' dissemination evidence file'.($count === 1 ? '' : 's').' uploaded')->success()->send();
    }

    private function storedDisseminationReports(): array
    {
        return collect(data_get($this->record->action_data ?? [], 'dissemination_reports', []))
            ->map(fn ($value): string => (string) $value)
            ->all();
    }

    private function disseminationOrganisationKey(array $partner, int $index): string
    {
        if (filled($partner['oid'] ?? null)) {
            return 'oid_'.Str::slug((string) $partner['oid'], '_');
        }

        $base = trim(($partner['name'] ?? 'organisation').'|'.($partner['country'] ?? '').'|'.$index);

        return 'org_'.substr(sha1($base), 0, 12);
    }
}
