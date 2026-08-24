<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\BudgetLine;
use App\Models\BudgetTransfer;
use App\Models\Expense;
use App\Services\BudgetTransferService;
use App\Support\AuthorizesProjectManagement;
use DomainException;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class ViewProjectBoard extends Page
{
    use AuthorizesProjectManagement;
    use InteractsWithRecord;
    use WithFileUploads;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.pages.view-project-board';

    // ─── State pentru modale ───
    public bool $showBasketModal = false;

    public ?int $editingBasketId = null;

    public string $basketTitle = '';

    public string $basketEmoji = '📁';

    public string $basketColor = '#6366f1';

    public bool $showNotesModal = false;

    public ?int $notesExpenseId = null;

    public string $notesText = '';

    public $uploadFile = null;

    public ?int $uploadExpenseId = null;

    // Transfer state
    public bool $showTransferModal = false;

    public ?int $transferFromId = null;

    public ?int $transferToId = null;

    public $transferAmount = null;

    public string $transferReason = '';

    private const OPTIONAL_BASKET_PRESETS = [
        'ka2_project_management' => ['title' => 'Project Management', 'emoji' => '📋', 'color' => '#0f766e'],
        'inclusion' => ['title' => 'Inclusion Support', 'emoji' => '🤝', 'color' => '#ec4899'],
        'preparatory_visits' => ['title' => 'Preparatory Visits', 'emoji' => '🧭', 'color' => '#0284c7'],
        'course_fees' => ['title' => 'Course Fees', 'emoji' => '🎓', 'color' => '#7c3aed'],
        'linguistic_support' => ['title' => 'Linguistic Support', 'emoji' => '🗣️', 'color' => '#2563eb'],
        'exceptional_costs' => ['title' => 'Exceptional Costs', 'emoji' => '⚡', 'color' => '#f59e0b'],
    ];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        ProjectResource::ensureProjectAccountTenant($this->record, 'board');
        $this->authorizeProjectModuleAccess('board');
        $this->touchProjectCollaboration('board');
    }

    public function getTitle(): string
    {
        return $this->record->name;
    }

    public function getCurrencies(): array
    {
        return $this->record->currencyRates();
    }

    private function extractRate($value): float
    {
        if (is_array($value)) {
            return (float) ($value['rate'] ?? 1);
        }
        if (is_numeric($value)) {
            return (float) $value;
        }

        return 1.0;
    }

    private function reload(): void
    {
        $this->record->load(['budgetLines' => fn ($q) => $q->orderBy('sort_order'), 'budgetLines.expenses']);
    }

    protected function syncProjectCollaborationState(string $module): void
    {
        if ($module !== 'board') {
            return;
        }

        $hasOwnLock = $this->projectLocksForModule('board')
            ->contains(fn ($lock) => (int) $lock->user_id === (int) auth()->id());

        if (! $hasOwnLock && ! $this->showBasketModal && ! $this->showNotesModal && ! $this->showTransferModal && $this->uploadExpenseId === null) {
            $this->reload();
        }
    }

    public function startBasketEditing(int $basketId): void
    {
        $line = BudgetLine::where('project_id', $this->record->id)->find($basketId);

        if (! $line) {
            return;
        }

        $this->startProjectEditing('board', $this->basketLockKey($line->id), $this->basketLockLabel($line));
    }

    public function startExpenseEditing(int $expenseId): void
    {
        $expense = $this->findExpense($expenseId);

        if (! $expense) {
            return;
        }

        $this->startProjectEditing('board', $this->expenseLockKey($expense->id), $this->expenseLockLabel($expense));
    }

    protected function basketLockKey(int $basketId): string
    {
        return 'basket:'.$basketId;
    }

    protected function expenseLockKey(int $expenseId): string
    {
        return 'expense:'.$expenseId;
    }

    protected function basketLockLabel(BudgetLine $line): string
    {
        return 'Basket: '.$line->title;
    }

    protected function expenseLockLabel(Expense $expense): string
    {
        return 'Expense '.$this->expenseCode($expense);
    }

    // ═══════════ BUGET COȘ (inline) ═══════════
    public function updateBasketBudget(int $basketId, $value): void
    {
        $line = BudgetLine::where('project_id', $this->record->id)->find($basketId);
        if (! $line) {
            return;
        }

        $this->authorizeManagementModuleMutation('board', $this->basketLockKey($line->id), $this->basketLockLabel($line));
        $line->allocated_budget = (float) $value;
        $line->save();
        $this->reload();
    }

    // ═══════════ COȘURI: add / edit / delete ═══════════
    public function openBasketCreate(): void
    {
        $this->editingBasketId = null;
        $this->basketTitle = '';
        $this->basketEmoji = '📁';
        $this->basketColor = '#6366f1';
        $this->showBasketModal = true;
    }

    public function applyBasketPreset(string $preset): void
    {
        $values = self::OPTIONAL_BASKET_PRESETS[$preset] ?? null;

        if (! $values) {
            return;
        }

        $this->basketTitle = $values['title'];
        $this->basketEmoji = $values['emoji'];
        $this->basketColor = $values['color'];
    }

    public function openBasketEdit(int $basketId): void
    {
        $line = BudgetLine::where('project_id', $this->record->id)->find($basketId);
        if (! $line) {
            return;
        }

        $this->startProjectEditing('board', $this->basketLockKey($line->id), $this->basketLockLabel($line));
        $this->editingBasketId = $line->id;
        $this->basketTitle = $line->title;
        $this->basketEmoji = $line->emoji ?? '📁';
        $this->basketColor = $line->color ?? '#6366f1';
        $this->showBasketModal = true;
    }

    public function saveBasket(): void
    {
        $data = [
            'title' => trim($this->basketTitle) ?: 'Untitled',
            'emoji' => trim($this->basketEmoji) ?: '📁',
            'color' => $this->basketColor ?: '#6366f1',
        ];

        if ($this->editingBasketId) {
            $line = BudgetLine::where('project_id', $this->record->id)->find($this->editingBasketId);
            if (! $line) {
                return;
            }

            $this->authorizeManagementModuleMutation('board', $this->basketLockKey($line->id), $this->basketLockLabel($line));
            $line->update($data);
        } else {
            $this->authorizeManagementModuleMutation('board', 'baskets', 'Budget baskets');
            $maxSort = BudgetLine::where('project_id', $this->record->id)->max('sort_order') ?? -1;
            $this->record->budgetLines()->create(array_merge($data, [
                'allocated_budget' => 0,
                'sort_order' => $maxSort + 1,
            ]));
        }

        $this->showBasketModal = false;
        $this->reload();
    }

    public function deleteBasket(int $basketId): void
    {
        $line = BudgetLine::where('project_id', $this->record->id)->with('expenses')->find($basketId);
        if (! $line) {
            return;
        }

        $this->authorizeManagementModuleMutation('board', $this->basketLockKey($line->id), $this->basketLockLabel($line));
        $line->delete();
        $this->reload();
    }

    // ═══════════ CHELTUIELI ═══════════
    public function addExpense(int $budgetLineId): void
    {
        $line = BudgetLine::where('project_id', $this->record->id)->findOrFail($budgetLineId);
        $this->authorizeManagementModuleMutation('board', $this->basketLockKey($line->id), $this->basketLockLabel($line));
        $maxPos = Expense::where('budget_line_id', $line->id)->max('position') ?? -1;

        Expense::create([
            'budget_line_id' => $line->id,
            'description' => '',
            'expense_date' => now()->toDateString(),
            'amount' => 0,
            'currency' => 'EUR',
            'exchange_rate' => 1,
            'amount_eur' => 0,
            'is_civil_convention' => false,
            'position' => $maxPos + 1,
            'created_by' => auth()->id(),
        ]);
        $this->reload();
    }

    public function updateExpense(int $expenseId, string $field, $value): void
    {
        $expense = $this->findExpense($expenseId);
        if (! $expense) {
            return;
        }

        $this->authorizeManagementModuleMutation('board', $this->expenseLockKey($expense->id), $this->expenseLockLabel($expense));

        $currencies = $this->getCurrencies();

        if ($field === 'amount' || $field === 'currency') {
            if ($field === 'amount') {
                $expense->amount = (float) $value;
            }
            if ($field === 'currency') {
                $expense->currency = $value;
            }

            // Daca moneda nu are curs definit in project currencies, NU converti silentios 1:1.
            if ($expense->currency !== 'EUR' && ! array_key_exists($expense->currency, $currencies)) {
                $expense->exchange_rate = null;
                $expense->amount_eur = 0;
                $expense->save();
                $this->reload();
                Notification::make()
                    ->title('No exchange rate for '.$expense->currency)
                    ->body('Add a rate in Project settings → Project currencies, then re-enter the amount. This expense counts as € 0 until then.')
                    ->warning()
                    ->send();

                return;
            }

            $rate = $this->extractRate($currencies[$expense->currency] ?? 1);
            $expense->exchange_rate = $rate;
            $expense->amount_eur = $rate > 0 ? round((float) $expense->amount / $rate, 2) : (float) $expense->amount;
        } elseif ($field === 'is_civil_convention') {
            $expense->is_civil_convention = (bool) $value;
        } elseif (in_array($field, ['description', 'expense_date'])) {
            $expense->{$field} = $value;
        }

        $expense->save();
        $this->reload();
    }

    public function deleteExpense(int $expenseId): void
    {
        $expense = $this->findExpense($expenseId);
        if ($expense) {
            $this->authorizeManagementModuleMutation('board', $this->expenseLockKey($expense->id), $this->expenseLockLabel($expense));
        }
        $expense?->delete();
        $this->reload();
    }

    private function findExpense(int $id): ?Expense
    {
        return Expense::whereHas('budgetLine', fn ($q) => $q->where('project_id', $this->record->id))->find($id);
    }

    // ═══════════ NOTE ═══════════
    public function openNotes(int $expenseId): void
    {
        $expense = $this->findExpense($expenseId);
        if (! $expense) {
            return;
        }

        $this->startProjectEditing('board', $this->expenseLockKey($expense->id), $this->expenseLockLabel($expense));
        $this->notesExpenseId = $expense->id;
        $this->notesText = $expense->notes ?? '';
        $this->showNotesModal = true;
    }

    public function saveNotes(): void
    {
        $expense = $this->findExpense($this->notesExpenseId);
        if ($expense) {
            $this->authorizeManagementModuleMutation('board', $this->expenseLockKey($expense->id), $this->expenseLockLabel($expense));
            $expense->notes = $this->notesText;
            $expense->save();
        }
        $this->showNotesModal = false;
        $this->reload();
    }

    // ═══════════ ATAȘAMENTE ═══════════
    public function updatedUploadFile(): void
    {
        if (! $this->uploadFile || ! $this->uploadExpenseId) {
            return;
        }

        $this->validate([
            'uploadFile' => 'file|max:10240|mimes:pdf,jpg,jpeg,png,gif,webp,doc,docx,xls,xlsx',
        ]);

        $expense = $this->findExpense($this->uploadExpenseId);
        if (! $expense) {
            return;
        }

        $this->authorizeManagementModuleMutation('board', $this->expenseLockKey($expense->id), $this->expenseLockLabel($expense));

        if ($expense->attachmentExists()) {
            Storage::disk($expense->attachment_disk ?: 'local')->delete($expense->attachment_path);
        }

        $extension = $this->uploadFile->getClientOriginalExtension()
            ?: pathinfo((string) $this->uploadFile->getClientOriginalName(), PATHINFO_EXTENSION);
        $filename = $expense->supportingFileName($this->record, $extension);
        $path = $this->uploadFile->storeAs('expenses/'.$expense->id, $filename, 'local');
        $expense->attachment_path = $path;
        $expense->attachment_disk = 'local';
        $expense->attachment_name = $filename;
        $expense->save();

        $this->uploadFile = null;
        $this->uploadExpenseId = null;
        $this->reload();
    }

    public function setUploadTarget(int $expenseId): void
    {
        $this->startExpenseEditing($expenseId);
        $this->uploadExpenseId = $expenseId;
    }

    public function deleteAttachment(int $expenseId): void
    {
        $expense = $this->findExpense($expenseId);
        if ($expense) {
            $this->authorizeManagementModuleMutation('board', $this->expenseLockKey($expense->id), $this->expenseLockLabel($expense));
        }
        if ($expense && $expense->attachmentExists()) {
            Storage::disk($expense->attachment_disk ?: 'local')->delete($expense->attachment_path);
        }
        if ($expense) {
            $expense->attachment_path = null;
            $expense->attachment_name = null;
            $expense->save();
        }
        $this->reload();
    }

    // ═══════════ TRANSFERURI ═══════════
    public function getTransfers()
    {
        return BudgetTransfer::where('project_id', $this->record->id)
            ->with(['fromLine', 'toLine'])
            ->latest()
            ->get();
    }

    public function openTransfer(): void
    {
        $this->transferFromId = null;
        $this->transferToId = null;
        $this->transferAmount = null;
        $this->transferReason = '';
        $this->showTransferModal = true;
    }

    public function saveTransfer(): void
    {
        $this->authorizeManagementModuleMutation('board', 'transfer-budget', 'Budget transfers');
        $this->validate([
            'transferFromId' => 'required|different:transferToId',
            'transferToId' => 'required',
            'transferAmount' => 'required|numeric|min:0.01',
        ], [
            'transferFromId.different' => 'Source and destination must differ.',
        ]);

        $from = BudgetLine::where('project_id', $this->record->id)->find($this->transferFromId);
        $to = BudgetLine::where('project_id', $this->record->id)->find($this->transferToId);
        if (! $from || ! $to) {
            return;
        }

        try {
            app(BudgetTransferService::class)->transfer(
                $this->record,
                $from,
                $to,
                (float) $this->transferAmount,
                $this->transferReason,
                auth()->user(),
            );
        } catch (DomainException $exception) {
            $this->addError('transferAmount', $exception->getMessage());

            return;
        }

        $this->showTransferModal = false;
        $this->reload();
    }

    public function reverseTransfer(int $transferId): void
    {
        $this->authorizeManagementModuleMutation('board', 'transfer-budget', 'Budget transfers');
        $transfer = BudgetTransfer::where('project_id', $this->record->id)->find($transferId);
        if (! $transfer || ! $transfer->isActive()) {
            return;
        }

        try {
            app(BudgetTransferService::class)->reverse($this->record, $transfer);
        } catch (DomainException $exception) {
            Notification::make()
                ->title('Transfer could not be reversed')
                ->body($exception->getMessage())
                ->warning()
                ->send();

            return;
        }

        $this->reload();
    }

    public function expenseCode(Expense $expense): string
    {
        $prefix = $this->record->expense_prefix ?: 'EXP';
        $pad = (int) ($this->record->expense_pad_length ?: 3);

        return '#'.$prefix.'-'.str_pad((string) $expense->id, $pad, '0', STR_PAD_LEFT);
    }
}
