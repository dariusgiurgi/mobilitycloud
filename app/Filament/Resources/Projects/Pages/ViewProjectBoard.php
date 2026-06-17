<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use App\Models\BudgetLine;
use App\Models\Expense;
use App\Models\BudgetTransfer;
use Illuminate\Support\Facades\DB;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Facades\Filament;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class ViewProjectBoard extends Page
{
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

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string
    {
        return $this->record->name;
    }

    public function getCurrencies(): array
    {
        $currencies = Filament::getTenant()?->currencies ?? [];
        return array_merge(['EUR' => 1], $currencies);
    }

    private function extractRate($value): float
    {
        if (is_array($value))  return (float) ($value['rate'] ?? 1);
        if (is_numeric($value)) return (float) $value;
        return 1.0;
    }

    private function reload(): void
    {
        $this->record->load(['budgetLines' => fn ($q) => $q->orderBy('sort_order'), 'budgetLines.expenses']);
    }

    // ═══════════ BUGET COȘ (inline) ═══════════
    public function updateBasketBudget(int $basketId, $value): void
    {
        $line = BudgetLine::where('project_id', $this->record->id)->find($basketId);
        if (!$line) return;
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

    public function openBasketEdit(int $basketId): void
    {
        $line = BudgetLine::where('project_id', $this->record->id)->find($basketId);
        if (!$line) return;
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
            BudgetLine::where('project_id', $this->record->id)->where('id', $this->editingBasketId)->update($data);
        } else {
            $maxSort = BudgetLine::where('project_id', $this->record->id)->max('sort_order') ?? -1;
            $this->record->budgetLines()->create(array_merge($data, [
                'allocated_budget' => 0,
                'sort_order'       => $maxSort + 1,
            ]));
        }

        $this->showBasketModal = false;
        $this->reload();
    }

    public function deleteBasket(int $basketId): void
    {
        BudgetLine::where('project_id', $this->record->id)->where('id', $basketId)->delete();
        $this->reload();
    }

    // ═══════════ CHELTUIELI ═══════════
    public function addExpense(int $budgetLineId): void
    {
        $line = BudgetLine::where('project_id', $this->record->id)->findOrFail($budgetLineId);
        $maxPos = Expense::where('budget_line_id', $line->id)->max('position') ?? -1;

        Expense::create([
            'budget_line_id'      => $line->id,
            'description'         => '',
            'expense_date'        => now()->toDateString(),
            'amount'              => 0,
            'currency'            => 'EUR',
            'exchange_rate'       => 1,
            'amount_eur'          => 0,
            'is_civil_convention' => false,
            'position'            => $maxPos + 1,
            'created_by'          => auth()->id(),
        ]);
        $this->reload();
    }

    public function updateExpense(int $expenseId, string $field, $value): void
    {
        $expense = $this->findExpense($expenseId);
        if (!$expense) return;

        $currencies = $this->getCurrencies();

        if ($field === 'amount' || $field === 'currency') {
            if ($field === 'amount')   $expense->amount = (float) $value;
            if ($field === 'currency') $expense->currency = $value;
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
        if ($expense && $expense->attachment_path && Storage::disk('public')->exists($expense->attachment_path)) {
            Storage::disk('public')->delete($expense->attachment_path);
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
        if (!$expense) return;
        $this->notesExpenseId = $expense->id;
        $this->notesText = $expense->notes ?? '';
        $this->showNotesModal = true;
    }

    public function saveNotes(): void
    {
        $expense = $this->findExpense($this->notesExpenseId);
        if ($expense) {
            $expense->notes = $this->notesText;
            $expense->save();
        }
        $this->showNotesModal = false;
        $this->reload();
    }

    // ═══════════ ATAȘAMENTE ═══════════
    public function updatedUploadFile(): void
    {
        if (!$this->uploadFile || !$this->uploadExpenseId) return;

        $this->validate([
            'uploadFile' => 'file|max:10240|mimes:pdf,jpg,jpeg,png,gif,webp,doc,docx,xls,xlsx',
        ]);

        $expense = $this->findExpense($this->uploadExpenseId);
        if (!$expense) return;

        if ($expense->attachment_path && Storage::disk('public')->exists($expense->attachment_path)) {
            Storage::disk('public')->delete($expense->attachment_path);
        }

        $path = $this->uploadFile->store('expenses', 'public');
        $expense->attachment_path = $path;
        $expense->attachment_name = $this->uploadFile->getClientOriginalName();
        $expense->save();

        $this->uploadFile = null;
        $this->uploadExpenseId = null;
        $this->reload();
    }

    public function setUploadTarget(int $expenseId): void
    {
        $this->uploadExpenseId = $expenseId;
    }

    public function deleteAttachment(int $expenseId): void
    {
        $expense = $this->findExpense($expenseId);
        if ($expense && $expense->attachment_path && Storage::disk('public')->exists($expense->attachment_path)) {
            Storage::disk('public')->delete($expense->attachment_path);
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
        $this->validate([
            'transferFromId' => 'required|different:transferToId',
            'transferToId'   => 'required',
            'transferAmount' => 'required|numeric|min:0.01',
        ], [
            'transferFromId.different' => 'Source and destination must differ.',
        ]);

        $from = BudgetLine::where('project_id', $this->record->id)->find($this->transferFromId);
        $to   = BudgetLine::where('project_id', $this->record->id)->find($this->transferToId);
        if (!$from || !$to) return;

        $amount = (float) $this->transferAmount;

        DB::transaction(function () use ($from, $to, $amount) {
            $fromLocked = BudgetLine::lockForUpdate()->find($from->id);
            $toLocked   = BudgetLine::lockForUpdate()->find($to->id);

            $fromLocked->allocated_budget = (float) $fromLocked->allocated_budget - $amount;
            $fromLocked->save();
            $toLocked->allocated_budget = (float) $toLocked->allocated_budget + $amount;
            $toLocked->save();

            BudgetTransfer::create([
                'project_id'          => $this->record->id,
                'from_budget_line_id' => $from->id,
                'to_budget_line_id'   => $to->id,
                'amount'              => $amount,
                'reason'              => $this->transferReason ?: null,
                'status'              => 'active',
                'created_by'          => auth()->id(),
            ]);
        });

        $this->showTransferModal = false;
        $this->reload();
    }

    public function reverseTransfer(int $transferId): void
    {
        $transfer = BudgetTransfer::where('project_id', $this->record->id)->find($transferId);
        if (!$transfer || !$transfer->isActive()) return;

        DB::transaction(function () use ($transfer) {
            $from = BudgetLine::lockForUpdate()->find($transfer->from_budget_line_id);
            $to   = BudgetLine::lockForUpdate()->find($transfer->to_budget_line_id);

            if ($to)   { $to->allocated_budget   = (float) $to->allocated_budget - (float) $transfer->amount; $to->save(); }
            if ($from) { $from->allocated_budget = (float) $from->allocated_budget + (float) $transfer->amount; $from->save(); }

            $transfer->update(['status' => 'reversed', 'reversed_at' => now()]);
        });

        $this->reload();
    }

    public function expenseCode(Expense $expense): string
    {
        $prefix = $this->record->expense_prefix ?: 'EXP';
        $pad = (int) ($this->record->expense_pad_length ?: 3);
        return '#' . $prefix . '-' . str_pad((string) $expense->id, $pad, '0', STR_PAD_LEFT);
    }
}
