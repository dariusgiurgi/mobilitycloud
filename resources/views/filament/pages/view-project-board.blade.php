<x-filament-panels::page>
    <x-ui-polish />

    @php
        $currencies = array_keys($this->getCurrencies());
        $totalBudget = (float) $record->effective_budget;
        $totalSpent  = $record->spent;
        $totalRemaining = $totalBudget - $totalSpent;
        $totalAllocated = (float) $record->budgetLines->sum('allocated_budget');
        $unallocated = $totalBudget - $totalAllocated;
        $expenseCount = $record->budgetLines->sum(fn ($line) => $line->expenses->count());
        $documentedCount = $record->budgetLines->sum(fn ($line) => $line->expenses->whereNotNull('attachment_path')->count());
        $canManage = $record->canBeManagedBy(auth()->user());
        $emojiList = ['📁','✈️','🙋','🏢','🤝','⚡','🎓','❤️','💼','📚','🍽️','🚗','🏨','🎫','💻','📞','🎨','🔧','💡','🎯'];
    @endphp

    {{-- Scoate sagetile de la inputuri numerice --}}
    <style>
        .mc-board input[type=number]::-webkit-outer-spin-button,
        .mc-board input[type=number]::-webkit-inner-spin-button { -webkit-appearance:none; margin:0; }
        .mc-board input[type=number] { -moz-appearance:textfield; appearance:textfield; }
        .mc-board select { color:#18181b; }
        .dark .mc-board select { color:#f4f4f5; }
        .dark .mc-board select option { background:#27303f; color:#f4f4f5; }
        .mc-board input[type=date] { color-scheme: light; }
        .dark .mc-board input[type=date] { color-scheme: dark; }
        .mc-budget-header { display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap; }
        .mc-budget-stats { display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem;margin:1rem 0; }
        .mc-budget-progress { height:7px;border-radius:9999px;background:rgba(148,163,184,.22);overflow:hidden;margin-top:.65rem; }
        @media (max-width:1000px) { .mc-budget-stats { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media (max-width:600px) { .mc-budget-stats { grid-template-columns:1fr; } }
    </style>

    <div class="mc-board">

    <x-filament::section>
        <div class="mc-budget-header">
            <div>
                <div style="display:flex;align-items:center;gap:.45rem;">
                    <h2 class="text-gray-950 dark:text-white" style="font-size:.95rem;font-weight:650;">Budget control</h2>
                    <x-help-tip id="budget-control" title="Implementation budget">
                        Expenses are recorded in their original currency and converted to EUR using the workspace rate. Basket allocations organise the approved grant but do not change its total.
                    </x-help-tip>
                </div>
                <p class="text-gray-500 dark:text-gray-400" style="font-size:.72rem;margin-top:.2rem;">{{ $expenseCount }} expenses · {{ $documentedCount }} with supporting files @if(!$canManage) · Read-only access @endif</p>
            </div>
            <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;">
                <x-filament::button tag="a" :href="route('projects.export', $record)" target="_blank" color="gray" icon="heroicon-o-arrow-down-tray" size="sm">Export PDF</x-filament::button>
                @if($canManage)
                    <x-filament::button wire:click="openTransfer" color="gray" icon="heroicon-o-arrows-right-left" size="sm">Transfer budget</x-filament::button>
                @endif
            </div>
        </div>
    </x-filament::section>

    {{-- ── Summary bar ── --}}
    <div class="mc-budget-stats">
        <div class="mc-stat">
            <p class="mc-stat-label">Total Budget</p>
            <p class="mc-stat-value">€ {{ number_format($totalBudget, 2) }}</p>
        </div>
        <div class="mc-stat">
            <p class="mc-stat-label">Spent (EUR)</p>
            <p class="mc-stat-value">€ {{ number_format($totalSpent, 2) }}</p>
        </div>
        <div class="mc-stat">
            <p class="mc-stat-label">Remaining</p>
            <p class="mc-stat-value {{ $totalRemaining < 0 ? 'mc-neg' : 'mc-pos' }}">€ {{ number_format($totalRemaining, 2) }}</p>
        </div>
        <div class="mc-stat">
            <p class="mc-stat-label">{{ $unallocated >= 0 ? 'Unallocated' : 'Over-allocated' }}</p>
            <p class="mc-stat-value {{ $unallocated < 0 ? 'mc-neg' : '' }}">€ {{ number_format(abs($unallocated), 2) }}</p>
        </div>
    </div>

    <div style="margin:-.25rem 0 1.5rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;font-size:.7rem;" class="text-gray-500 dark:text-gray-400"><span>Grant used</span><span>{{ $totalBudget > 0 ? min(100, (int) round($totalSpent / $totalBudget * 100)) : 0 }}%</span></div>
        <div class="mc-budget-progress"><div style="height:100%;width:{{ $totalBudget > 0 ? min(100, $totalSpent / $totalBudget * 100) : 0 }}%;background:{{ $totalRemaining < 0 ? '#dc2626' : '#6366f1' }};border-radius:9999px;"></div></div>
        @if(abs($unallocated) > .009)
            <p style="font-size:.7rem;margin-top:.45rem;color:{{ $unallocated < 0 ? '#dc2626' : '#d97706' }};">Basket allocations {{ $unallocated < 0 ? 'exceed' : 'are below' }} the effective grant by € {{ number_format(abs($unallocated), 2) }}.</p>
        @endif
    </div>

    {{-- ── Budget lines ── --}}
    @foreach($record->budgetLines as $line)
        @php
            $lineSpent = $line->expenses->sum('amount_eur');
            $lineAlloc = (float) $line->allocated_budget;
            $lineLeft  = $lineAlloc - $lineSpent;
            $color = $line->color ?: '#6366f1';
        @endphp

        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
             style="border-left:4px solid {{ $color }};margin-bottom:1rem;overflow:hidden;">

            <div style="display:flex;align-items:center;justify-content:space-between;padding:.85rem 1.1rem;gap:1rem;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:.5rem;">
                    <span style="font-size:18px;">{{ $line->emoji ?? '📁' }}</span>
                    <span class="text-gray-950 dark:text-white" style="font-weight:600;font-size:14px;">{{ $line->title }}</span>
                </div>
                <div style="display:flex;align-items:center;gap:1rem;font-size:12px;flex-wrap:wrap;">
                    <span class="text-gray-500 dark:text-gray-400" style="display:inline-flex;align-items:center;gap:4px;">
                        Budget: €
                        @if($canManage)
                            <input type="number" step="0.01" min="0" value="{{ $lineAlloc }}"
                                wire:key="basket-alloc-{{ $line->id }}-{{ $lineAlloc }}"
                                wire:change="updateBasketBudget({{ $line->id }}, $event.target.value)"
                                class="text-gray-950 dark:text-white"
                                style="width:90px;text-align:right;background:transparent;border:1px solid rgba(100,116,139,.25);border-radius:4px;padding:3px 6px;font-size:12px;font-weight:600;">
                        @else
                            <strong class="text-gray-950 dark:text-white">{{ number_format($lineAlloc, 2) }}</strong>
                        @endif
                    <span class="text-gray-500 dark:text-gray-400">Spent: <strong class="text-gray-950 dark:text-white">€ {{ number_format($lineSpent, 2) }}</strong></span>
                    <span class="text-gray-500 dark:text-gray-400">Left: <strong class="{{ $lineLeft < 0 ? 'mc-neg' : 'mc-pos' }}">€ {{ number_format($lineLeft, 2) }}</strong></span>

                    @if($canManage)
                    <button type="button" wire:click="addExpense({{ $line->id }})"
                            style="display:inline-flex;align-items:center;gap:4px;padding:5px 12px;border-radius:8px;background:#6366f1;color:#fff;font-size:12px;font-weight:500;border:none;cursor:pointer;">
                        + Add expense
                    </button>
                    <button type="button" wire:click="openBasketEdit({{ $line->id }})" title="Edit basket"
                            style="width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;border-radius:6px;border:none;background:transparent;cursor:pointer;color:#9ca3af;"
                            onmouseover="this.style.background='rgba(99,102,241,.1)';this.style.color='#6366f1';"
                            onmouseout="this.style.background='transparent';this.style.color='#9ca3af';">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg>
                    </button>
                    <button type="button" wire:click="deleteBasket({{ $line->id }})" wire:confirm="Delete this basket and all its expenses?" title="Delete basket"
                            style="width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;border-radius:6px;border:none;background:transparent;cursor:pointer;color:#9ca3af;"
                            onmouseover="this.style.background='rgba(239,68,68,.1)';this.style.color='#dc2626';"
                            onmouseout="this.style.background='transparent';this.style.color='#9ca3af';">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </button>
                    @endif
                </div>
            </div>

            @if($line->expenses->count() > 0)
            <div class="mc-table-scroll" style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:12px;min-width:1000px;">
                    <thead>
                        <tr class="text-gray-500 dark:text-gray-400" style="background:rgba(100,116,139,.06);font-size:10px;text-transform:uppercase;letter-spacing:.04em;">
                            <th style="padding:8px 10px;text-align:left;width:90px;">Code</th>
                            <th style="padding:8px 10px;text-align:left;">Description</th>
                            <th style="padding:8px 10px;text-align:left;width:130px;">Date</th>
                            <th style="padding:8px 10px;text-align:center;width:52px;">
                                <span style="display:inline-flex;align-items:center;gap:.25rem;">
                                    CC
                                    <x-help-tip id="budget-civil-convention-{{ $line->id }}" title="Civil convention">
                                        Select CC when this expense must generate a civil agreement. The expense will then appear automatically in Documents → Civil conventions, where optional payment evidence can also be prepared.
                                    </x-help-tip>
                                </span>
                            </th>
                            <th style="padding:8px 10px;text-align:right;width:95px;">Amount</th>
                            <th style="padding:8px 10px;text-align:center;width:75px;">Currency</th>
                            <th style="padding:8px 10px;text-align:right;width:95px;">EUR</th>
                            <th style="padding:8px 10px;text-align:center;width:40px;">File</th>
                            <th style="padding:8px 10px;text-align:center;width:40px;">Note</th>
                            <th style="padding:8px 10px;text-align:center;width:40px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($line->expenses->sortBy('position') as $expense)
                        <tr class="text-gray-950 dark:text-white" style="border-top:1px solid rgba(100,116,139,.12);">
                            <td style="padding:6px 10px;font-family:monospace;font-size:11px;" class="text-gray-400">{{ $this->expenseCode($expense) }}</td>
                            <td style="padding:6px 10px;">
                                <input type="text" value="{{ $expense->description }}"
                                       wire:change="updateExpense({{ $expense->id }}, 'description', $event.target.value)"
                                       placeholder="Expense name…"
                                       @readonly(!$canManage)
                                       style="width:100%;background:transparent;border:1px solid transparent;border-radius:4px;padding:4px 6px;color:inherit;font-size:12px;">
                            </td>
                            <td style="padding:6px 10px;">
                                <input type="date" value="{{ $expense->expense_date?->format('Y-m-d') }}"
                                       wire:change="updateExpense({{ $expense->id }}, 'expense_date', $event.target.value)"
                                       @disabled(!$canManage)
                                       style="width:100%;background:transparent;border:1px solid rgba(100,116,139,.25);border-radius:4px;padding:4px 6px;color:inherit;font-size:12px;">
                            </td>
                            <td style="padding:6px 10px;text-align:center;">
                                <input type="checkbox" {{ $expense->is_civil_convention ? 'checked' : '' }}
                                       wire:change="updateExpense({{ $expense->id }}, 'is_civil_convention', $event.target.checked)"
                                       @disabled(!$canManage)
                                       style="width:15px;height:15px;cursor:pointer;accent-color:#6366f1;">
                            </td>
                            <td style="padding:6px 10px;">
                                <input type="number" step="0.01" min="0" value="{{ $expense->amount }}"
                                       wire:change="updateExpense({{ $expense->id }}, 'amount', $event.target.value)"
                                       @disabled(!$canManage)
                                       style="width:100%;text-align:right;background:transparent;border:1px solid rgba(100,116,139,.25);border-radius:4px;padding:4px 6px;color:inherit;font-size:12px;">
                            </td>
                            <td style="padding:6px 10px;">
                                <select wire:change="updateExpense({{ $expense->id }}, 'currency', $event.target.value)"
                                        @disabled(!$canManage)
                                        style="width:100%;background:transparent;border:1px solid rgba(100,116,139,.25);border-radius:4px;padding:4px 6px;color:inherit;font-size:12px;">
                                    @foreach($currencies as $cur)
                                        <option value="{{ $cur }}" {{ $expense->currency === $cur ? 'selected' : '' }} class="dark:bg-gray-800">{{ $cur }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td style="padding:6px 10px;text-align:right;font-weight:600;">€ {{ number_format((float) $expense->amount_eur, 2) }}</td>

                            <td style="padding:6px 10px;text-align:center;">
                                @if($expense->attachment_path)
                                    <span style="display:inline-flex;align-items:center;gap:2px;">
                                        <a href="{{ route('attachments.expenses.download', $expense) }}" target="_blank" title="{{ $expense->attachment_name }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 17.99 8.8l-8.57 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>
                                        </a>
                                        @if($canManage)
                                        <button type="button" wire:click="deleteAttachment({{ $expense->id }})" title="Remove file"
                                                style="border:none;background:transparent;cursor:pointer;color:#dc2626;font-size:12px;line-height:1;">×</button>
                                        @endif
                                    </span>
                                @elseif($canManage)
                                    <label style="cursor:pointer;display:inline-flex;" title="Upload file"
                                           wire:click="setUploadTarget({{ $expense->id }})">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 17.99 8.8l-8.57 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>
                                        <input type="file" wire:model="uploadFile" style="display:none;" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.xls,.xlsx">
                                    </label>
                                @endif
                            </td>

                            <td style="padding:6px 10px;text-align:center;">
                                <button type="button" wire:click="openNotes({{ $expense->id }})" title="Notes"
                                        style="border:none;background:transparent;cursor:pointer;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="{{ $expense->notes ? '#6366f1' : '#9ca3af' }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                                </button>
                            </td>

                            <td style="padding:6px 10px;text-align:center;">
                                @if($canManage)
                                <button type="button" wire:click="deleteExpense({{ $expense->id }})" wire:confirm="Delete this expense?"
                                        style="width:26px;height:26px;display:inline-flex;align-items:center;justify-content:center;border-radius:6px;border:none;background:transparent;cursor:pointer;color:#9ca3af;"
                                        onmouseover="this.style.background='rgba(239,68,68,.1)';this.style.color='#dc2626';"
                                        onmouseout="this.style.background='transparent';this.style.color='#9ca3af';">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                </button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="mc-empty-state text-gray-400" style="padding:.75rem 1.1rem;font-size:12px;font-style:italic;">No expenses yet.</div>
            @endif
        </div>
    @endforeach

    @if($canManage)
    <button type="button" wire:click="openBasketCreate"
            class="text-gray-500 dark:text-gray-400"
            style="width:100%;padding:14px;border:2px dashed rgba(100,116,139,.3);border-radius:12px;background:transparent;cursor:pointer;font-size:13px;font-weight:500;display:flex;align-items:center;justify-content:center;gap:.5rem;">
        + Add new basket
    </button>
    @endif

    {{-- ═══════════ BASKET MODAL ═══════════ --}}
    @if($showBasketModal)
    <div class="mc-modal-backdrop"
         wire:click.self="$set('showBasketModal', false)">
        <div class="mc-modal mc-modal-panel"><div class="mc-modal-body">
            <h3 class="mc-modal-title mc-modal-heading" style="margin-bottom:1rem;">{{ $editingBasketId ? 'Edit basket' : 'Add basket' }}</h3>

            <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;margin-bottom:4px;" class="mc-modal-label">Title</label>
            <input type="text" wire:model="basketTitle"
                   class="mc-modal-input"
                   style="width:100%;padding:8px 12px;border:1px solid rgba(100,116,139,.3);border-radius:6px;margin-bottom:1rem;font-size:14px;">

            <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;margin-bottom:4px;" class="mc-modal-label">Emoji</label>
            <input type="text" wire:model="basketEmoji" maxlength="4"
                   class="mc-modal-input"
                   style="width:70px;padding:8px;border:1px solid rgba(100,116,139,.3);border-radius:6px;font-size:20px;text-align:center;margin-bottom:8px;">
            <div style="display:grid;grid-template-columns:repeat(10,1fr);gap:4px;margin-bottom:1.25rem;">
                @foreach($emojiList as $em)
                    <button type="button" wire:click="$set('basketEmoji', '{{ $em }}')"
                            class="mc-emoji-btn"
                            style="aspect-ratio:1;border-radius:6px;border:1px solid rgba(100,116,139,.2);cursor:pointer;font-size:15px;display:inline-flex;align-items:center;justify-content:center;">{{ $em }}</button>
                @endforeach
            </div>

            <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;margin-bottom:4px;" class="mc-modal-label">Color</label>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:1.25rem;">
                <input type="color" wire:model="basketColor"
                       style="width:48px;height:36px;border:1px solid rgba(100,116,139,.3);border-radius:6px;cursor:pointer;background:transparent;">
                @foreach(['#3b82f6','#22c55e','#8b5cf6','#ec4899','#f59e0b','#ef4444'] as $c)
                    <button type="button" wire:click="$set('basketColor', '{{ $c }}')"
                            style="width:26px;height:26px;border-radius:6px;border:2px solid rgba(0,0,0,.1);background:{{ $c }};cursor:pointer;"></button>
                @endforeach
            </div>

            <div class="mc-modal-actions">
                <button type="button" wire:click="$set('showBasketModal', false)"
                        class="mc-modal-cancel"
                        style="padding:8px 16px;border-radius:8px;border:1px solid rgba(100,116,139,.3);background:transparent;cursor:pointer;font-size:13px;">Cancel</button>
                <button type="button" wire:click="saveBasket"
                        style="padding:8px 16px;border-radius:8px;border:none;background:#6366f1;color:#fff;cursor:pointer;font-size:13px;font-weight:500;">Save</button>
            </div>
        </div></div>
    </div>
    @endif

    {{-- ═══════════ NOTES MODAL ═══════════ --}}
    @if($showNotesModal)
    <div class="mc-modal-backdrop"
         wire:click.self="$set('showNotesModal', false)">
        <div class="mc-modal mc-modal-panel"><div class="mc-modal-body">
            <h3 class="mc-modal-title mc-modal-heading" style="margin-bottom:1rem;">Notes</h3>
            <textarea wire:model="notesText" rows="6" placeholder="Add observations about this expense…"
                      @readonly(!$canManage)
                      class="mc-modal-input"
                      style="width:100%;padding:10px 12px;border:1px solid rgba(100,116,139,.3);border-radius:6px;font-size:14px;resize:vertical;"></textarea>
            <div class="mc-modal-actions" style="margin-top:1rem;">
                <button type="button" wire:click="$set('showNotesModal', false)"
                        class="mc-modal-cancel"
                        style="padding:8px 16px;border-radius:8px;border:1px solid rgba(100,116,139,.3);background:transparent;cursor:pointer;font-size:13px;">Cancel</button>
                @if($canManage)
                <button type="button" wire:click="saveNotes"
                        style="padding:8px 16px;border-radius:8px;border:none;background:#6366f1;color:#fff;cursor:pointer;font-size:13px;font-weight:500;">Save notes</button>
                @endif
            </div>
        </div></div>
    </div>
    @endif

    {{-- ═══════════ TRANSFERS HISTORY ═══════════ --}}
    @php $transfers = $this->getTransfers(); @endphp
    @if($transfers->count() > 0)
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="margin-top:1.5rem;overflow:hidden;">
        <div style="padding:.85rem 1.1rem;border-bottom:1px solid rgba(100,116,139,.12);">
            <span class="text-gray-950 dark:text-white" style="font-weight:600;font-size:14px;">Budget transfers</span>
        </div>
        <div class="mc-table-scroll" style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:12px;min-width:700px;">
                <thead>
                    <tr class="text-gray-500 dark:text-gray-400" style="background:rgba(100,116,139,.06);font-size:10px;text-transform:uppercase;letter-spacing:.04em;">
                        <th style="padding:8px 10px;text-align:left;">Date</th>
                        <th style="padding:8px 10px;text-align:left;">From → To</th>
                        <th style="padding:8px 10px;text-align:right;">Amount</th>
                        <th style="padding:8px 10px;text-align:left;">Reason</th>
                        <th style="padding:8px 10px;text-align:center;">Status</th>
                        <th style="padding:8px 10px;text-align:center;width:44px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transfers as $tr)
                    <tr class="text-gray-950 dark:text-white" style="border-top:1px solid rgba(100,116,139,.12);">
                        <td style="padding:7px 10px;white-space:nowrap;" class="text-gray-500 dark:text-gray-400">{{ $tr->created_at->format('d M Y, H:i') }}</td>
                        <td style="padding:7px 10px;">{{ $tr->fromLine->title ?? '?' }} <span class="text-gray-400">→</span> {{ $tr->toLine->title ?? '?' }}</td>
                        <td style="padding:7px 10px;text-align:right;font-weight:600;">€ {{ number_format((float) $tr->amount, 2) }}</td>
                        <td style="padding:7px 10px;" class="text-gray-500 dark:text-gray-400">{{ $tr->reason ?: '—' }}</td>
                        <td style="padding:7px 10px;text-align:center;">
                            @if($tr->isActive())
                                <span style="font-size:11px;font-weight:600;padding:2px 10px;border-radius:999px;background:rgba(34,197,94,.15);color:#16a34a;">Active</span>
                            @else
                                <span style="font-size:11px;font-weight:600;padding:2px 10px;border-radius:999px;background:rgba(100,116,139,.15);color:#64748b;">Reversed</span>
                            @endif
                        </td>
                        <td style="padding:7px 10px;text-align:center;">
                            @if($tr->isActive() && $canManage)
                                <button type="button" wire:click="reverseTransfer({{ $tr->id }})" wire:confirm="Reverse this transfer? Money returns to the original basket." title="Reverse"
                                        style="width:26px;height:26px;display:inline-flex;align-items:center;justify-content:center;border-radius:6px;border:none;background:transparent;cursor:pointer;color:#9ca3af;"
                                        onmouseover="this.style.background='rgba(245,158,11,.15)';this.style.color='#d97706';"
                                        onmouseout="this.style.background='transparent';this.style.color='#9ca3af';">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7v6h6"></path><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"></path></svg>
                                </button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ═══════════ TRANSFER MODAL ═══════════ --}}
    @if($showTransferModal)
    <div class="mc-modal-backdrop"
         wire:click.self="$set('showTransferModal', false)">
        <div class="mc-modal mc-modal-panel"><div class="mc-modal-body">
            <h3 class="mc-modal-title mc-modal-heading" style="margin-bottom:1rem;">Transfer budget</h3>

            <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;margin-bottom:4px;" class="mc-modal-label">From basket</label>
            <select wire:model="transferFromId" class="mc-modal-input" style="width:100%;padding:8px 12px;border:1px solid rgba(100,116,139,.3);border-radius:6px;margin-bottom:1rem;font-size:14px;">
                <option value="">— Select source —</option>
                @foreach($record->budgetLines as $bl)
                    @php $av = (float)$bl->allocated_budget - $bl->expenses->sum('amount_eur'); @endphp
                    <option value="{{ $bl->id }}">{{ $bl->emoji }} {{ $bl->title }} (Available: € {{ number_format($av, 2) }})</option>
                @endforeach
            </select>

            <div style="text-align:center;font-size:18px;margin-bottom:.5rem;" class="text-gray-400">↓</div>

            <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;margin-bottom:4px;" class="mc-modal-label">To basket</label>
            <select wire:model="transferToId" class="mc-modal-input" style="width:100%;padding:8px 12px;border:1px solid rgba(100,116,139,.3);border-radius:6px;margin-bottom:1rem;font-size:14px;">
                <option value="">— Select destination —</option>
                @foreach($record->budgetLines as $bl)
                    <option value="{{ $bl->id }}">{{ $bl->emoji }} {{ $bl->title }}</option>
                @endforeach
            </select>

            <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;margin-bottom:4px;" class="mc-modal-label">Amount (€)</label>
            <input type="number" step="0.01" min="0.01" wire:model="transferAmount" placeholder="0.00"
                   class="mc-modal-input" style="width:100%;padding:8px 12px;border:1px solid rgba(100,116,139,.3);border-radius:6px;margin-bottom:1rem;font-size:14px;text-align:right;">

            <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;margin-bottom:4px;" class="mc-modal-label">Reason (optional)</label>
            <input type="text" wire:model="transferReason" maxlength="500" placeholder="e.g. Reallocate to course fees"
                   class="mc-modal-input" style="width:100%;padding:8px 12px;border:1px solid rgba(100,116,139,.3);border-radius:6px;margin-bottom:1.25rem;font-size:14px;">

            @error('transferFromId') <p style="color:#dc2626;font-size:12px;margin:-8px 0 12px;">{{ $message }}</p> @enderror
            @error('transferAmount') <p style="color:#dc2626;font-size:12px;margin:-8px 0 12px;">{{ $message }}</p> @enderror

            <div class="mc-modal-actions">
                <button type="button" wire:click="$set('showTransferModal', false)" class="mc-modal-cancel" style="padding:8px 16px;border-radius:8px;border:1px solid rgba(100,116,139,.3);background:transparent;cursor:pointer;font-size:13px;">Cancel</button>
                <button type="button" wire:click="saveTransfer" style="padding:8px 16px;border-radius:8px;border:none;background:#6366f1;color:#fff;cursor:pointer;font-size:13px;font-weight:500;">Transfer</button>
            </div>
        </div></div>
    </div>
    @endif

    </div>

    {{-- Modal theming: fundal solid in ambele moduri --}}
    <style>
        .mc-stat { background:#ffffff; border:1px solid #e4e4e7; border-radius:12px; padding:1rem 1.25rem; }
        .mc-stat-label { color:#71717a; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.04em; margin:0; }
        .mc-stat-value { color:#18181b; font-size:22px; font-weight:700; margin:6px 0 0; }
        .mc-pos, .dark .mc-pos, .dark .mc-stat-value.mc-pos { color:#16a34a !important; }
        .mc-neg, .dark .mc-neg, .dark .mc-stat-value.mc-neg { color:#dc2626 !important; }
        .dark .mc-stat { background:#18212f !important; border-color:rgba(148,163,184,.15) !important; }
        .dark .mc-stat-label { color:#a1a1aa !important; }
        .dark .mc-stat-value { color:#f4f4f5 !important; }
        .mc-modal { background:#ffffff; }
        .mc-modal-title { color:#18181b; }
        .mc-modal-label { color:#71717a; }
        .mc-modal-input { background:#fafafa; color:#18181b; }
        .mc-modal-cancel { color:#3f3f46; }
        .mc-emoji-btn { background:#fafafa; color:#18181b; }
        .dark .mc-modal { background:#18212f !important; }
        .dark .mc-modal-title { color:#f4f4f5 !important; }
        .dark .mc-modal-label { color:#a1a1aa !important; }
        .dark .mc-modal-input { background:#27303f !important; color:#f4f4f5 !important; border-color:rgba(148,163,184,.3) !important; }
        .dark .mc-modal-cancel { color:#d4d4d8 !important; }
        .dark .mc-emoji-btn { background:#27303f !important; color:#f4f4f5 !important; }
    </style>

</x-filament-panels::page>
