<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Expense;
use App\Models\ProjectDocument;
use App\Services\ExpenseReportSnapshot;
use App\Services\ProjectDocumentChecklist;
use App\Support\AuthorizesProjectManagement;
use App\Support\GeneratesAttendanceSheets;
use Carbon\Carbon;
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

    public bool $showExpenseReportModal = false;

    public string $reportTitle = 'Official expense report';

    public ?string $reportStartDate = null;

    public ?string $reportEndDate = null;

    public ?string $reportDate = null;

    public string $reportPlace = '';

    public string $reportPreparedBy = '';

    public string $reportPreparedByRole = '';

    public string $reportNotes = '';

    public string $reportOrderBy = 'date';

    public bool $reportPageBreakByCategory = false;

    public bool $showConventionSignedUploadModal = false;

    public ?int $conventionSignedExpenseId = null;

    public string $conventionSignedKind = 'agreement';

    public $conventionSignedUpload = null;

    public string $documentFilter = 'all';

    public string $documentSearch = '';

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
        return ProjectDocument::query()
            ->where('project_id', $this->record->id)
            ->when($this->documentFilter === 'generated', fn ($query) => $query->whereIn('type', [
                ProjectDocument::TYPE_ATTENDANCE,
                ProjectDocument::TYPE_EXPENSE_REPORT,
            ]))
            ->when($this->documentFilter === 'uploaded', fn ($query) => $query->where('type', ProjectDocument::TYPE_UPLOAD))
            ->when($this->documentFilter === 'signed', fn ($query) => $query->whereNotNull('signed_path'))
            ->when(filled($this->documentSearch), function ($query): void {
                $search = '%'.trim($this->documentSearch).'%';
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', $search)
                        ->orWhere('file_name', 'like', $search)
                        ->orWhere('notes', 'like', $search);
                });
            })
            ->latest('id')
            ->get();
    }

    public function getDocumentChecklist(): array
    {
        return app(ProjectDocumentChecklist::class)->build($this->record);
    }

    public function setDocumentFilter(string $filter): void
    {
        $this->documentFilter = in_array($filter, ['all', 'generated', 'uploaded', 'signed'], true)
            ? $filter
            : 'all';
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

    public function openExpenseReportGenerator(): void
    {
        $this->authorizeProjectManagement();
        $this->resetValidation();
        $this->reportTitle = 'Official expense report';
        $this->reportStartDate = $this->record->start_date?->format('Y-m-d');
        $this->reportEndDate = $this->record->end_date?->format('Y-m-d');
        $this->reportDate = now()->toDateString();
        $this->reportPlace = '';
        $this->reportPreparedBy = auth()->user()?->name ?? '';
        $this->reportPreparedByRole = '';
        $this->reportNotes = '';
        $this->reportOrderBy = 'date';
        $this->reportPageBreakByCategory = false;
        $this->showExpenseReportModal = true;
    }

    public function closeExpenseReportGenerator(): void
    {
        $this->showExpenseReportModal = false;
    }

    public function generateExpenseReport(ExpenseReportSnapshot $snapshotBuilder): void
    {
        $this->authorizeProjectManagement();
        $validated = $this->validate([
            'reportTitle' => ['required', 'string', 'max:255'],
            'reportStartDate' => ['nullable', 'date'],
            'reportEndDate' => ['nullable', 'date', 'after_or_equal:reportStartDate'],
            'reportDate' => ['required', 'date'],
            'reportPlace' => ['nullable', 'string', 'max:255'],
            'reportPreparedBy' => ['required', 'string', 'max:255'],
            'reportPreparedByRole' => ['nullable', 'string', 'max:255'],
            'reportNotes' => ['nullable', 'string', 'max:5000'],
            'reportOrderBy' => ['required', 'in:'.implode(',', array_keys(ExpenseReportSnapshot::ORDER_OPTIONS))],
            'reportPageBreakByCategory' => ['boolean'],
        ]);

        $startDate = filled($validated['reportStartDate'] ?? null) ? Carbon::parse($validated['reportStartDate'])->startOfDay() : null;
        $endDate = filled($validated['reportEndDate'] ?? null) ? Carbon::parse($validated['reportEndDate'])->endOfDay() : null;
        $snapshot = $snapshotBuilder->build($this->record, $startDate, $endDate, $validated['reportOrderBy']);

        $this->record->documents()->create([
            'type' => ProjectDocument::TYPE_EXPENSE_REPORT,
            'category' => 'report',
            'title' => trim($validated['reportTitle']),
            'document_date' => $validated['reportDate'],
            'notes' => trim($validated['reportNotes'] ?? '') ?: null,
            'metadata' => array_merge($snapshot, [
                'place' => trim($validated['reportPlace'] ?? ''),
                'prepared_by' => trim($validated['reportPreparedBy']),
                'prepared_by_role' => trim($validated['reportPreparedByRole'] ?? ''),
                'page_break_by_category' => $validated['reportOrderBy'] === 'category'
                    && (bool) ($validated['reportPageBreakByCategory'] ?? false),
            ]),
            'generated_at' => now(),
        ]);

        $this->closeExpenseReportGenerator();
        Notification::make()->title('Official expense report generated')->success()->send();
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
            'payment_date' => $expense->expense_date?->format('Y-m-d') ?? now()->toDateString(),
            'payment_method' => 'bank_transfer',
            'payment_status' => 'paid',
            'payment_reference' => '',
            'payment_notes' => '',
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
            'conventionData.payment_date' => ['nullable', 'date'],
            'conventionData.payment_method' => ['nullable', 'in:'.implode(',', array_keys(Expense::PAYMENT_METHODS))],
            'conventionData.payment_status' => ['nullable', 'in:'.implode(',', array_keys(Expense::PAYMENT_STATUSES))],
            'conventionData.payment_reference' => ['nullable', 'string', 'max:255'],
            'conventionData.payment_notes' => ['nullable', 'string', 'max:2000'],
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

    public function openConventionSignedUpload(int $expenseId, string $kind): void
    {
        $this->authorizeProjectManagement();
        abort_unless(in_array($kind, ['agreement', 'payment'], true), 404);
        $expense = $this->findConventionExpense($expenseId);
        if (! $expense) {
            return;
        }

        abort_unless(
            match ($kind) {
                'agreement' => $expense->hasCompleteConventionData(),
                'payment' => $expense->hasCompleteConventionData() && $expense->hasCompletePaymentData(),
            },
            422
        );

        $this->resetValidation('conventionSignedUpload');
        $this->conventionSignedExpenseId = $expense->id;
        $this->conventionSignedKind = $kind;
        $this->conventionSignedUpload = null;
        $this->showConventionSignedUploadModal = true;
    }

    public function closeConventionSignedUpload(): void
    {
        $this->showConventionSignedUploadModal = false;
        $this->conventionSignedExpenseId = null;
        $this->conventionSignedKind = 'agreement';
        $this->conventionSignedUpload = null;
    }

    public function uploadConventionSignedCopy(): void
    {
        $this->authorizeProjectManagement();
        $this->validate([
            'conventionSignedKind' => ['required', 'in:agreement,payment'],
            'conventionSignedUpload' => ['required', 'file', 'max:20480', 'mimes:pdf,jpg,jpeg,png'],
        ]);

        $expense = $this->findConventionExpense($this->conventionSignedExpenseId);
        if (! $expense) {
            $this->closeConventionSignedUpload();

            return;
        }

        $kind = $this->conventionSignedKind;
        abort_unless(
            match ($kind) {
                'agreement' => $expense->hasCompleteConventionData(),
                'payment' => $expense->hasCompleteConventionData() && $expense->hasCompletePaymentData(),
            },
            422
        );
        $existing = $expense->conventionSignedCopy($kind);
        if ($expense->hasConventionSignedCopy($kind)) {
            Storage::disk($existing['disk'])->delete($existing['path']);
        }

        $extension = strtolower($this->conventionSignedUpload->getClientOriginalExtension() ?: 'pdf');
        $filename = 'signed_'.$kind.'_'.Str::slug($expense->description ?: 'civil-convention').'_'.$expense->id.'.'.$extension;
        $path = $this->conventionSignedUpload->storeAs(
            'project-documents/'.$this->record->id.'/civil-conventions/'.$expense->id,
            $filename,
            'local'
        );
        $data = $expense->convention_data ?? [];
        $data[$kind.'_signed_path'] = $path;
        $data[$kind.'_signed_disk'] = 'local';
        $data[$kind.'_signed_name'] = $this->conventionSignedUpload->getClientOriginalName();
        $data[$kind.'_signed_size'] = $this->conventionSignedUpload->getSize();
        $data[$kind.'_signed_at'] = now()->toIso8601String();
        $expense->update(['convention_data' => $data]);

        $this->closeConventionSignedUpload();
        Notification::make()->title(ucfirst($kind).' signed copy uploaded')->success()->send();
    }

    public function deleteConventionSignedCopy(int $expenseId, string $kind): void
    {
        $this->authorizeProjectManagement();
        abort_unless(in_array($kind, ['agreement', 'payment'], true), 404);
        $expense = $this->findConventionExpense($expenseId);
        if (! $expense) {
            return;
        }

        $copy = $expense->conventionSignedCopy($kind);
        if ($expense->hasConventionSignedCopy($kind)) {
            Storage::disk($copy['disk'])->delete($copy['path']);
        }

        $data = $expense->convention_data ?? [];
        foreach (['path', 'disk', 'name', 'size', 'at'] as $field) {
            unset($data[$kind.'_signed_'.$field]);
        }
        $expense->update(['convention_data' => $data]);
        Notification::make()->title(ucfirst($kind).' signed copy removed')->success()->send();
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
