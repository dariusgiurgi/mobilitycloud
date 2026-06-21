<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Expense;
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

    public bool $showConventionModal = false;

    public ?int $conventionExpenseId = null;

    public array $conventionData = [];

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

    public function getCivilConventionExpenses()
    {
        return Expense::query()
            ->where('is_civil_convention', true)
            ->whereHas('budgetLine', fn ($query) => $query->where('project_id', $this->record->id))
            ->with('budgetLine')
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->get();
    }

    public function openConvention(int $expenseId): void
    {
        $this->authorizeProjectManagement();
        $expense = $this->findConventionExpense($expenseId);
        if (! $expense) {
            return;
        }

        $workspace = $this->record->workspace;
        $saved = $expense->convention_data ?? [];
        $this->conventionExpenseId = $expense->id;
        $this->conventionData = array_merge([
            'agreement_type' => 'service_agreement',
            'convention_number' => $expense->reference_nr ?: $this->expenseCode($expense),
            'contract_date' => $expense->expense_date?->format('Y-m-d') ?? now()->toDateString(),
            'contract_place' => '',
            'beneficiary_name' => $workspace?->billing_name ?: $workspace?->name,
            'beneficiary_vat' => $workspace?->billing_vat,
            'beneficiary_address' => $workspace?->billing_address,
            'beneficiary_representative' => '',
            'beneficiary_representative_role' => '',
            'provider_name' => '',
            'provider_nationality' => '',
            'provider_id_type' => 'Passport',
            'provider_id_number' => '',
            'provider_personal_number' => '',
            'provider_address' => '',
            'provider_email' => '',
            'provider_bank_name' => '',
            'provider_iban' => '',
            'service_description' => $expense->description,
            'service_start_date' => $this->record->mobility_start_date?->format('Y-m-d'),
            'service_end_date' => $this->record->mobility_end_date?->format('Y-m-d'),
            'service_location' => '',
            'gross_amount' => (string) $expense->amount,
            'currency' => $expense->currency,
            'payment_due_days' => '10',
            'work_description' => '',
            'rights_exclusive' => true,
            'rights_scope' => 'Reproduction; distribution; public display and communication; adaptation; translation; and digital publication.',
            'use_methods' => 'Print, digital, online, social media, project reporting, dissemination and archival use.',
            'rights_duration' => 'For the full legal term of protection',
            'rights_territory' => 'Worldwide',
            'right_to_sublicense' => true,
        ], $saved);
        $this->resetValidation('conventionData');
        $this->showConventionModal = true;
    }

    public function closeConvention(): void
    {
        $this->showConventionModal = false;
        $this->conventionExpenseId = null;
        $this->conventionData = [];
    }

    public function saveConventionDetails(): void
    {
        $this->authorizeProjectManagement();
        $expense = $this->findConventionExpense($this->conventionExpenseId);
        if (! $expense) {
            $this->closeConvention();

            return;
        }

        $this->validate([
            'conventionData.agreement_type' => ['required', 'in:'.implode(',', array_keys(Expense::CONVENTION_TYPES))],
            'conventionData.convention_number' => ['nullable', 'string', 'max:100'],
            'conventionData.contract_date' => ['nullable', 'date'],
            'conventionData.contract_place' => ['nullable', 'string', 'max:255'],
            'conventionData.beneficiary_name' => ['nullable', 'string', 'max:255'],
            'conventionData.beneficiary_vat' => ['nullable', 'string', 'max:100'],
            'conventionData.beneficiary_address' => ['nullable', 'string', 'max:500'],
            'conventionData.beneficiary_representative' => ['nullable', 'string', 'max:255'],
            'conventionData.beneficiary_representative_role' => ['nullable', 'string', 'max:255'],
            'conventionData.provider_name' => ['nullable', 'string', 'max:255'],
            'conventionData.provider_nationality' => ['nullable', 'string', 'max:100'],
            'conventionData.provider_id_type' => ['nullable', 'string', 'max:100'],
            'conventionData.provider_id_number' => ['nullable', 'string', 'max:100'],
            'conventionData.provider_personal_number' => ['nullable', 'string', 'max:100'],
            'conventionData.provider_address' => ['nullable', 'string', 'max:500'],
            'conventionData.provider_email' => ['nullable', 'email', 'max:255'],
            'conventionData.provider_bank_name' => ['nullable', 'string', 'max:255'],
            'conventionData.provider_iban' => ['nullable', 'string', 'max:100'],
            'conventionData.service_description' => ['nullable', 'string', 'max:2000'],
            'conventionData.service_start_date' => ['nullable', 'date'],
            'conventionData.service_end_date' => ['nullable', 'date', 'after_or_equal:conventionData.service_start_date'],
            'conventionData.service_location' => ['nullable', 'string', 'max:255'],
            'conventionData.gross_amount' => ['nullable', 'numeric', 'min:0'],
            'conventionData.currency' => ['nullable', 'string', 'max:10'],
            'conventionData.payment_due_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'conventionData.work_description' => ['nullable', 'string', 'max:5000'],
            'conventionData.rights_exclusive' => ['boolean'],
            'conventionData.rights_scope' => ['nullable', 'string', 'max:5000'],
            'conventionData.use_methods' => ['nullable', 'string', 'max:5000'],
            'conventionData.rights_duration' => ['nullable', 'string', 'max:255'],
            'conventionData.rights_territory' => ['nullable', 'string', 'max:255'],
            'conventionData.right_to_sublicense' => ['boolean'],
        ]);

        $expense->update(['convention_data' => $this->conventionData]);
        $this->closeConvention();
        Notification::make()->title('Civil convention details saved')->success()->send();
    }

    public function expenseCode(Expense $expense): string
    {
        $prefix = $this->record->expense_prefix ?: 'EXP';
        $pad = (int) ($this->record->expense_pad_length ?: 3);

        return $prefix.'-'.str_pad((string) $expense->id, $pad, '0', STR_PAD_LEFT);
    }

    private function findConventionExpense(?int $expenseId): ?Expense
    {
        if (! $expenseId) {
            return null;
        }

        return Expense::query()
            ->where('is_civil_convention', true)
            ->whereHas('budgetLine', fn ($query) => $query->where('project_id', $this->record->id))
            ->find($expenseId);
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
