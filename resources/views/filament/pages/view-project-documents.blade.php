<x-filament-panels::page>
    @php
        $documents = $this->getDocuments();
        $documentCategories = $this->getDocumentCategories();
        $civilConventions = $this->getCivilConventionExpenses();
        $checklist = $this->getDocumentChecklist();
    @endphp

    <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;flex-wrap:wrap;">
        <p class="text-gray-500 dark:text-gray-400" style="font-size:13px;margin:0;">The project's private repository for generated records and supporting files.</p>
        <div style="flex:1;"></div>
        @if($record->canBeManagedBy(auth()->user()))
            <button type="button" wire:click="openDocumentUpload"
                    style="padding:8px 15px;border-radius:8px;border:1px solid rgba(100,116,139,.3);background:transparent;cursor:pointer;font-size:13px;font-weight:600;"
                    class="text-gray-700 dark:text-gray-200">
                Upload document
            </button>
            <button type="button" wire:click="openAttendanceGenerator"
                    style="padding:8px 15px;border-radius:8px;border:none;background:#4f46e5;color:#fff;cursor:pointer;font-size:13px;font-weight:600;">
                Generate attendance list
            </button>
            <button type="button" wire:click="openExpenseReportGenerator"
                    style="padding:8px 15px;border-radius:8px;border:none;background:#0f766e;color:#fff;cursor:pointer;font-size:13px;font-weight:600;">
                Generate expense report
            </button>
        @endif
    </div>

    <details open class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="margin-bottom:1.5rem;overflow:hidden;">
        <summary style="padding:1rem 1.1rem;cursor:pointer;display:flex;align-items:center;gap:.75rem;list-style:none;flex-wrap:wrap;">
            <div>
                <h2 class="text-gray-950 dark:text-white" style="font-size:15px;font-weight:700;margin:0;">Project file checklist</h2>
                <p class="text-gray-500 dark:text-gray-400" style="font-size:11px;margin:2px 0 0;">Recommended file completeness overview</p>
            </div>
            <div style="flex:1;"></div>
            <span style="font-size:11px;color:#15803d;font-weight:700;">{{ $checklist['complete'] }} complete</span>
            @if($checklist['attention'])<span style="font-size:11px;color:#b45309;font-weight:700;">{{ $checklist['attention'] }} need attention</span>@endif
            @if($checklist['missing'])<span style="font-size:11px;color:#dc2626;font-weight:700;">{{ $checklist['missing'] }} missing</span>@endif
        </summary>
        <div style="padding:0 1.1rem 1.1rem;display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:.6rem;">
            @foreach($checklist['items'] as $item)
                @php
                    $checklistStyle = match($item['status']) {
                        'complete' => ['#dcfce7', '#166534', '✓'],
                        'attention' => ['#fef3c7', '#92400e', '!'],
                        'missing' => ['#fee2e2', '#991b1b', '×'],
                        default => ['#f1f5f9', '#64748b', '–'],
                    };
                @endphp
                <div style="padding:.75rem;border:1px solid rgba(148,163,184,.25);border-radius:9px;display:flex;gap:.65rem;align-items:flex-start;">
                    <span style="width:21px;height:21px;flex:none;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;background:{{ $checklistStyle[0] }};color:{{ $checklistStyle[1] }};font-size:11px;font-weight:800;">{{ $checklistStyle[2] }}</span>
                    <div>
                        <div class="text-gray-950 dark:text-white" style="font-size:12px;font-weight:700;">{{ $item['label'] }}</div>
                        <div class="text-gray-500 dark:text-gray-400" style="font-size:10px;margin-top:2px;line-height:1.35;">{{ $item['detail'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </details>

    <div style="margin:0 0 1.5rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:.65rem;">
            <div>
                <h2 class="text-gray-950 dark:text-white" style="font-size:15px;font-weight:700;margin:0;">Civil conventions</h2>
                <p class="text-gray-500 dark:text-gray-400" style="font-size:11px;margin:2px 0 0;">Expenses marked CC in Budget appear here automatically.</p>
            </div>
            <span class="text-gray-500 dark:text-gray-400" style="font-size:12px;">{{ $civilConventions->count() }}</span>
        </div>

        @if($civilConventions->isEmpty())
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 text-gray-500 dark:text-gray-400"
                 style="padding:1rem 1.1rem;font-size:12px;">
                No expenses are marked for a civil convention.
            </div>
        @else
            <div style="display:flex;flex-direction:column;gap:.6rem;">
                @foreach($civilConventions as $expense)
                    <details class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="width:100%;overflow:hidden;">
                        <summary style="padding:.85rem 1.1rem;display:flex;align-items:center;gap:.75rem;cursor:pointer;list-style:none;flex-wrap:wrap;">
                            <div style="font-size:20px;">📝</div>
                            <div style="flex:1;min-width:180px;">
                                <div class="text-gray-950 dark:text-white" style="font-size:13px;font-weight:700;">{{ $expense->description ?: 'Untitled civil convention' }}</div>
                                <div class="text-gray-500 dark:text-gray-400" style="font-size:11px;margin-top:3px;">
                                    {{ $this->expenseCode($expense) }} · {{ $expense->budgetLine?->title }} · {{ number_format((float) $expense->amount, 2) }} {{ $expense->currency }}
                                </div>
                            </div>
                            @if($expense->hasConventionSignedCopy('agreement'))
                                <span style="font-size:10px;color:#15803d;font-weight:700;">AGREEMENT SIGNED</span>
                            @endif
                            @if($expense->hasConventionSignedCopy('payment'))
                                <span style="font-size:10px;color:#15803d;font-weight:700;">PAYMENT SIGNED</span>
                            @endif
                            <span style="padding:4px 9px;border-radius:999px;font-size:10px;font-weight:700;background:{{ $expense->hasCompleteConventionData() ? '#dcfce7' : '#fef3c7' }};color:{{ $expense->hasCompleteConventionData() ? '#166534' : '#92400e' }};">
                                {{ $expense->hasCompleteConventionData() ? 'READY' : 'DETAILS NEEDED' }}
                            </span>
                            <span class="text-gray-400" style="font-size:12px;">⌄</span>
                        </summary>
                        <div style="padding:.75rem 1.1rem 1rem;border-top:1px solid rgba(148,163,184,.18);display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;">
                        @if($expense->hasCompleteConventionData())
                            <div style="display:flex;align-items:center;gap:.35rem;flex-wrap:wrap;">
                                <a href="{{ route('project-documents.civil-convention', [$record, $expense]) }}"
                                   style="padding:7px 11px;border-radius:7px;border:1px solid rgba(79,70,229,.3);color:#4338ca;text-decoration:none;font-size:12px;font-weight:600;">
                                    Agreement PDF
                                </a>
                                @if($expense->hasConventionSignedCopy('agreement'))
                                    <a href="{{ route('project-documents.convention-signed', [$record, $expense, 'agreement']) }}"
                                       style="padding:7px 11px;border-radius:7px;border:1px solid rgba(34,197,94,.35);color:#15803d;text-decoration:none;font-size:12px;font-weight:600;">Signed agreement</a>
                                @endif
                                @if($record->canBeManagedBy(auth()->user()))
                                    <button type="button" wire:click="openConventionSignedUpload({{ $expense->id }}, 'agreement')"
                                            style="padding:7px 10px;border-radius:7px;border:none;background:#eef2ff;color:#4338ca;cursor:pointer;font-size:11px;">
                                        {{ $expense->hasConventionSignedCopy('agreement') ? 'Replace signed' : 'Upload signed' }}
                                    </button>
                                    @if($expense->hasConventionSignedCopy('agreement'))
                                        <button type="button" wire:click="deleteConventionSignedCopy({{ $expense->id }}, 'agreement')" wire:confirm="Remove the signed agreement?"
                                                style="border:none;background:transparent;color:#dc2626;cursor:pointer;font-size:11px;">Remove</button>
                                    @endif
                                @endif
                            </div>
                        @endif
                        @if($expense->hasCompleteConventionData() && $expense->hasCompletePaymentData())
                            <div style="display:flex;align-items:center;gap:.35rem;flex-wrap:wrap;">
                                <a href="{{ route('project-documents.payment-statement', [$record, $expense]) }}"
                                   style="padding:7px 11px;border-radius:7px;border:1px solid rgba(180,83,9,.3);color:#b45309;text-decoration:none;font-size:12px;font-weight:600;">
                                    Payment statement
                                </a>
                                @if($expense->hasConventionSignedCopy('payment'))
                                    <a href="{{ route('project-documents.convention-signed', [$record, $expense, 'payment']) }}"
                                       style="padding:7px 11px;border-radius:7px;border:1px solid rgba(34,197,94,.35);color:#15803d;text-decoration:none;font-size:12px;font-weight:600;">Signed payment</a>
                                @endif
                                @if($record->canBeManagedBy(auth()->user()))
                                    <button type="button" wire:click="openConventionSignedUpload({{ $expense->id }}, 'payment')"
                                            style="padding:7px 10px;border-radius:7px;border:none;background:#fff7ed;color:#b45309;cursor:pointer;font-size:11px;">
                                        {{ $expense->hasConventionSignedCopy('payment') ? 'Replace signed' : 'Upload signed' }}
                                    </button>
                                    @if($expense->hasConventionSignedCopy('payment'))
                                        <button type="button" wire:click="deleteConventionSignedCopy({{ $expense->id }}, 'payment')" wire:confirm="Remove the signed payment statement?"
                                                style="border:none;background:transparent;color:#dc2626;cursor:pointer;font-size:11px;">Remove</button>
                                    @endif
                                @endif
                            </div>
                        @endif
                        @if($record->canBeManagedBy(auth()->user()))
                            <button type="button" wire:click="openConvention({{ $expense->id }})"
                                    style="padding:7px 11px;border-radius:7px;border:none;background:#eef2ff;color:#4338ca;cursor:pointer;font-size:12px;">
                                {{ $expense->hasCompleteConventionData() ? 'Edit details' : 'Complete details' }}
                            </button>
                        @endif
                        </div>
                    </details>
                @endforeach
            </div>
        @endif
    </div>

    <div style="display:flex;align-items:center;gap:.65rem;flex-wrap:wrap;margin-bottom:.65rem;">
        <h2 class="text-gray-950 dark:text-white" style="font-size:15px;font-weight:700;margin:0;">Project files and generated documents</h2>
        <div style="flex:1;"></div>
        <input type="search" wire:model.live.debounce.300ms="documentSearch" placeholder="Search documents..."
               style="width:min(220px,100%);padding:7px 10px;border:1px solid rgba(100,116,139,.3);border-radius:8px;background:transparent;font-size:12px;">
        @foreach(['all' => 'All', 'generated' => 'Generated', 'uploaded' => 'Uploaded', 'signed' => 'Signed'] as $key => $label)
            <button type="button" wire:click="setDocumentFilter('{{ $key }}')"
                    style="padding:6px 10px;border-radius:999px;border:1px solid {{ $documentFilter === $key ? '#4f46e5' : 'rgba(100,116,139,.25)' }};background:{{ $documentFilter === $key ? '#eef2ff' : 'transparent' }};color:{{ $documentFilter === $key ? '#4338ca' : '#64748b' }};font-size:11px;font-weight:600;cursor:pointer;">
                {{ $label }}
            </button>
        @endforeach
    </div>

    @if($documents->isEmpty())
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
             style="padding:2.5rem;text-align:center;">
            <div style="font-size:34px;margin-bottom:.6rem;">📄</div>
            <h3 class="text-gray-950 dark:text-white" style="font-size:16px;font-weight:700;margin:0 0 .35rem;">{{ $documentFilter !== 'all' || filled($documentSearch) ? 'No matching documents' : 'No project documents yet' }}</h3>
            <p class="text-gray-500 dark:text-gray-400" style="font-size:13px;margin:0;">{{ $documentFilter !== 'all' || filled($documentSearch) ? 'Try another filter or search term.' : 'Upload a project file or generate the first attendance list.' }}</p>
        </div>
    @else
        <div style="display:flex;flex-direction:column;gap:.75rem;">
            @foreach($documents as $document)
                <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
                     style="padding:1rem 1.1rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
                    <div style="font-size:24px;">{{ $document->type === \App\Models\ProjectDocument::TYPE_ATTENDANCE ? '📋' : ($document->type === \App\Models\ProjectDocument::TYPE_EXPENSE_REPORT ? '📊' : '📄') }}</div>
                    <div style="flex:1;min-width:220px;">
                        <div class="text-gray-950 dark:text-white" style="font-size:14px;font-weight:700;">{{ $document->title }}</div>
                        @if($document->type === \App\Models\ProjectDocument::TYPE_ATTENDANCE)
                            <div class="text-gray-500 dark:text-gray-400" style="font-size:11px;margin-top:3px;">
                                Attendance · {{ $document->activity_date?->format('d M Y') }}
                                @if($document->location) · {{ $document->location }} @endif
                                · Generated {{ $document->generated_at?->format('d M Y, H:i') }}
                            </div>
                        @elseif($document->type === \App\Models\ProjectDocument::TYPE_EXPENSE_REPORT)
                            <div class="text-gray-500 dark:text-gray-400" style="font-size:11px;margin-top:3px;">
                                Expense report · {{ (int) data_get($document->metadata, 'expense_count', 0) }} records
                                · {{ number_format((float) data_get($document->metadata, 'total_eur', 0), 2) }} EUR
                                · Generated {{ $document->generated_at?->format('d M Y, H:i') }}
                            </div>
                            @if($document->notes)
                                <div class="text-gray-500 dark:text-gray-400" style="font-size:11px;margin-top:4px;">{{ $document->notes }}</div>
                            @endif
                        @else
                            <div class="text-gray-500 dark:text-gray-400" style="font-size:11px;margin-top:3px;">
                                {{ $document->categoryLabel() }}
                                @if($document->document_date) · {{ $document->document_date->format('d M Y') }} @endif
                                @if($document->file_name) · {{ $document->file_name }} ({{ $document->humanFileSize() }}) @endif
                            </div>
                            @if($document->notes)
                                <div class="text-gray-500 dark:text-gray-400" style="font-size:11px;margin-top:4px;">{{ $document->notes }}</div>
                            @endif
                        @endif
                    </div>

                    @if(in_array($document->type, [\App\Models\ProjectDocument::TYPE_ATTENDANCE, \App\Models\ProjectDocument::TYPE_EXPENSE_REPORT], true))
                        <span style="padding:4px 9px;border-radius:999px;font-size:11px;font-weight:700;background:{{ $document->hasSignedCopy() ? '#dcfce7' : '#fef3c7' }};color:{{ $document->hasSignedCopy() ? '#166534' : '#92400e' }};">
                            {{ $document->statusLabel() }}
                        </span>

                        <a href="{{ route($document->type === \App\Models\ProjectDocument::TYPE_ATTENDANCE ? 'project-documents.attendance' : 'project-documents.expense-report', [$record, $document]) }}"
                           style="padding:7px 11px;border-radius:7px;border:1px solid rgba(100,116,139,.3);text-decoration:none;font-size:12px;">
                            Download PDF
                        </a>

                        @if($document->hasSignedCopy())
                            <a href="{{ route('project-documents.signed', [$record, $document]) }}"
                               style="padding:7px 11px;border-radius:7px;border:1px solid rgba(34,197,94,.35);color:#15803d;text-decoration:none;font-size:12px;">
                                Signed copy
                            </a>
                        @endif

                        @if($record->canBeManagedBy(auth()->user()))
                            <button type="button" wire:click="openSignedUpload({{ $document->id }})"
                                    style="padding:7px 11px;border-radius:7px;border:none;background:#eef2ff;color:#4338ca;cursor:pointer;font-size:12px;">
                                {{ $document->hasSignedCopy() ? 'Replace signed' : 'Upload signed' }}
                            </button>
                            @if($document->hasSignedCopy())
                                <button type="button" wire:click="deleteSignedCopy({{ $document->id }})" wire:confirm="Remove the signed copy?"
                                        style="border:none;background:transparent;color:#dc2626;cursor:pointer;font-size:12px;">Remove signed</button>
                            @endif
                        @endif
                    @else
                        <span style="padding:4px 9px;border-radius:999px;font-size:11px;font-weight:700;background:#e0e7ff;color:#3730a3;">{{ $document->categoryLabel() }}</span>
                        <a href="{{ route('project-documents.file', [$record, $document]) }}"
                           style="padding:7px 11px;border-radius:7px;border:1px solid rgba(100,116,139,.3);text-decoration:none;font-size:12px;">
                            Download
                        </a>
                    @endif

                    @if($record->canBeManagedBy(auth()->user()))
                        <button type="button" wire:click="deleteDocument({{ $document->id }})" wire:confirm="Delete this document and its stored files?"
                                style="border:none;background:transparent;color:#9ca3af;cursor:pointer;font-size:14px;">✕</button>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @include('filament.partials.attendance-generator-modal')

    @if($showConventionSignedUploadModal)
        <div style="position:fixed;inset:0;z-index:80;background:rgba(15,23,42,.6);display:flex;align-items:center;justify-content:center;padding:1rem;"
             wire:click.self="closeConventionSignedUpload">
            <div class="fi-section rounded-xl bg-white shadow-xl ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10"
                 style="width:min(480px,100%);padding:1.4rem;">
                <h2 class="text-gray-950 dark:text-white" style="font-size:18px;font-weight:700;margin:0 0 .4rem;">Upload signed {{ $conventionSignedKind === 'payment' ? 'payment statement' : 'agreement' }}</h2>
                <p class="text-gray-500 dark:text-gray-400" style="font-size:12px;margin:0 0 1rem;">PDF, JPG or PNG, maximum 20 MB. The file is private and linked to this civil convention.</p>
                <input type="file" wire:model="conventionSignedUpload" accept=".pdf,.jpg,.jpeg,.png" style="width:100%;font-size:13px;">
                @error('conventionSignedUpload') <span style="display:block;color:#dc2626;font-size:11px;margin-top:5px;">{{ $message }}</span> @enderror
                <div style="display:flex;justify-content:flex-end;gap:.5rem;margin-top:1.25rem;">
                    <button type="button" wire:click="closeConventionSignedUpload" style="padding:8px 14px;border-radius:7px;border:1px solid rgba(100,116,139,.3);background:transparent;cursor:pointer;">Cancel</button>
                    <button type="button" wire:click="uploadConventionSignedCopy" wire:loading.attr="disabled" wire:target="uploadConventionSignedCopy,conventionSignedUpload"
                            style="padding:8px 14px;border-radius:7px;border:none;background:#4f46e5;color:#fff;cursor:pointer;font-weight:600;">
                        <span wire:loading.remove wire:target="uploadConventionSignedCopy,conventionSignedUpload">Upload signed copy</span>
                        <span wire:loading wire:target="uploadConventionSignedCopy,conventionSignedUpload">Uploading...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if($showExpenseReportModal)
        <div style="position:fixed;inset:0;z-index:70;background:rgba(15,23,42,.6);display:flex;align-items:flex-start;justify-content:center;padding:2rem 1rem;overflow-y:auto;"
             wire:click.self="closeExpenseReportGenerator">
            <div class="fi-section rounded-xl bg-white shadow-xl ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10"
                 style="width:min(680px,100%);padding:1.5rem;">
                <h2 class="text-gray-950 dark:text-white" style="font-size:18px;font-weight:700;margin:0;">Generate official expense report</h2>
                <p class="text-gray-500 dark:text-gray-400" style="font-size:12px;margin:.3rem 0 1.2rem;">The selected expenses and totals are saved as an immutable snapshot. You can then download, sign and upload the signed report.</p>

                @php
                    $reportFieldStyle = 'width:100%;padding:8px 10px;border:1px solid rgba(100,116,139,.3);border-radius:7px;background:transparent;';
                    $reportLabelStyle = 'display:block;font-size:10px;font-weight:700;color:#71717a;margin-bottom:4px;text-transform:uppercase;';
                @endphp

                <label style="{{ $reportLabelStyle }}">Report title *</label>
                <input wire:model="reportTitle" style="{{ $reportFieldStyle }}margin-bottom:.8rem;">
                @error('reportTitle') <span style="display:block;color:#dc2626;font-size:11px;margin-top:-7px;margin-bottom:7px;">{{ $message }}</span> @enderror

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem;margin-bottom:.8rem;">
                    <div><label style="{{ $reportLabelStyle }}">Period start</label><input type="date" wire:model="reportStartDate" style="{{ $reportFieldStyle }}"></div>
                    <div><label style="{{ $reportLabelStyle }}">Period end</label><input type="date" wire:model="reportEndDate" style="{{ $reportFieldStyle }}"></div>
                    <div><label style="{{ $reportLabelStyle }}">Report date *</label><input type="date" wire:model="reportDate" style="{{ $reportFieldStyle }}"></div>
                    <div><label style="{{ $reportLabelStyle }}">Place</label><input wire:model="reportPlace" style="{{ $reportFieldStyle }}"></div>
                    <div><label style="{{ $reportLabelStyle }}">Prepared by *</label><input wire:model="reportPreparedBy" style="{{ $reportFieldStyle }}"></div>
                    <div><label style="{{ $reportLabelStyle }}">Role</label><input wire:model="reportPreparedByRole" style="{{ $reportFieldStyle }}"></div>
                </div>
                @error('reportEndDate') <span style="display:block;color:#dc2626;font-size:11px;margin-bottom:7px;">{{ $message }}</span> @enderror
                @error('reportDate') <span style="display:block;color:#dc2626;font-size:11px;margin-bottom:7px;">{{ $message }}</span> @enderror
                @error('reportPreparedBy') <span style="display:block;color:#dc2626;font-size:11px;margin-bottom:7px;">{{ $message }}</span> @enderror

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem;margin-bottom:.8rem;align-items:end;">
                    <div>
                        <label style="{{ $reportLabelStyle }}">Order expenses by *</label>
                        <select wire:model.live="reportOrderBy" style="{{ $reportFieldStyle }}">
                            @foreach(\App\Services\ExpenseReportSnapshot::ORDER_OPTIONS as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if($reportOrderBy === 'category')
                        <label style="display:flex;align-items:center;gap:8px;padding:8px 0;font-size:12px;cursor:pointer;">
                            <input type="checkbox" wire:model="reportPageBreakByCategory">
                            Start each budget basket on a new page
                        </label>
                    @endif
                </div>

                <label style="{{ $reportLabelStyle }}">Notes / reporting context</label>
                <textarea rows="3" wire:model="reportNotes" style="{{ $reportFieldStyle }}resize:vertical;"></textarea>

                <div style="display:flex;justify-content:flex-end;gap:.5rem;margin-top:1.25rem;">
                    <button type="button" wire:click="closeExpenseReportGenerator" style="padding:8px 14px;border-radius:7px;border:1px solid rgba(100,116,139,.3);background:transparent;cursor:pointer;">Cancel</button>
                    <button type="button" wire:click="generateExpenseReport" wire:loading.attr="disabled" wire:target="generateExpenseReport"
                            style="padding:8px 14px;border-radius:7px;border:none;background:#0f766e;color:#fff;cursor:pointer;font-weight:600;">
                        <span wire:loading.remove wire:target="generateExpenseReport">Generate report</span>
                        <span wire:loading wire:target="generateExpenseReport">Generating...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if($showConventionModal)
        <div style="position:fixed;inset:0;z-index:70;background:rgba(15,23,42,.6);display:flex;align-items:flex-start;justify-content:center;padding:2rem 1rem;overflow-y:auto;"
             wire:click.self="closeConvention">
            <div class="fi-section rounded-xl bg-white shadow-xl ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10"
                 style="width:min(760px,100%);padding:1.5rem;">
                <h2 class="text-gray-950 dark:text-white" style="font-size:18px;font-weight:700;margin:0;">Civil convention details</h2>
                <p class="text-gray-500 dark:text-gray-400" style="font-size:12px;margin:.3rem 0 1.2rem;">Save a draft at any time. All required fields must be completed before generation.</p>

                @php
                    $fieldStyle = 'width:100%;padding:8px 10px;border:1px solid rgba(100,116,139,.3);border-radius:7px;background:transparent;';
                    $labelStyle = 'display:block;font-size:10px;font-weight:700;color:#71717a;margin-bottom:4px;text-transform:uppercase;';
                @endphp

                <label style="{{ $labelStyle }}">Agreement type *</label>
                <select wire:model.live="conventionData.agreement_type" style="{{ $fieldStyle }}margin-bottom:1.2rem;">
                    @foreach(\App\Models\Expense::CONVENTION_TYPES as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>

                <h3 class="text-gray-950 dark:text-white" style="font-size:12px;font-weight:700;margin:0 0 .7rem;text-transform:uppercase;">Contract</h3>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.8rem;margin-bottom:1.2rem;">
                    <div><label style="{{ $labelStyle }}">Number *</label><input wire:model="conventionData.convention_number" style="{{ $fieldStyle }}"></div>
                    <div><label style="{{ $labelStyle }}">Date *</label><input type="date" wire:model="conventionData.contract_date" style="{{ $fieldStyle }}"></div>
                    <div><label style="{{ $labelStyle }}">Place</label><input wire:model="conventionData.contract_place" style="{{ $fieldStyle }}"></div>
                </div>

                <h3 class="text-gray-950 dark:text-white" style="font-size:12px;font-weight:700;margin:0 0 .7rem;text-transform:uppercase;">Beneficiary organisation</h3>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem;margin-bottom:1.2rem;">
                    <div><label style="{{ $labelStyle }}">Legal name</label><input wire:model="conventionData.beneficiary_name" style="{{ $fieldStyle }}"></div>
                    <div><label style="{{ $labelStyle }}">VAT / registration no.</label><input wire:model="conventionData.beneficiary_vat" style="{{ $fieldStyle }}"></div>
                    <div style="grid-column:1/-1;"><label style="{{ $labelStyle }}">Address</label><input wire:model="conventionData.beneficiary_address" style="{{ $fieldStyle }}"></div>
                    <div><label style="{{ $labelStyle }}">Legal representative</label><input wire:model="conventionData.beneficiary_representative" style="{{ $fieldStyle }}"></div>
                    <div><label style="{{ $labelStyle }}">Representative role</label><input wire:model="conventionData.beneficiary_representative_role" style="{{ $fieldStyle }}"></div>
                </div>

                <h3 class="text-gray-950 dark:text-white" style="font-size:12px;font-weight:700;margin:0 0 .7rem;text-transform:uppercase;">Service provider</h3>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem;margin-bottom:1.2rem;">
                    <div><label style="{{ $labelStyle }}">Full name *</label><input wire:model="conventionData.provider_name" style="{{ $fieldStyle }}"></div>
                    <div><label style="{{ $labelStyle }}">Nationality</label><input wire:model="conventionData.provider_nationality" style="{{ $fieldStyle }}"></div>
                    <div><label style="{{ $labelStyle }}">ID type</label><input wire:model="conventionData.provider_id_type" style="{{ $fieldStyle }}"></div>
                    <div><label style="{{ $labelStyle }}">ID number *</label><input wire:model="conventionData.provider_id_number" style="{{ $fieldStyle }}"></div>
                    <div><label style="{{ $labelStyle }}">Personal / tax number</label><input wire:model="conventionData.provider_personal_number" style="{{ $fieldStyle }}"></div>
                    <div><label style="{{ $labelStyle }}">Email</label><input type="email" wire:model="conventionData.provider_email" style="{{ $fieldStyle }}"></div>
                    <div style="grid-column:1/-1;"><label style="{{ $labelStyle }}">Address *</label><input wire:model="conventionData.provider_address" style="{{ $fieldStyle }}"></div>
                    <div><label style="{{ $labelStyle }}">Bank</label><input wire:model="conventionData.provider_bank_name" style="{{ $fieldStyle }}"></div>
                    <div><label style="{{ $labelStyle }}">IBAN</label><input wire:model="conventionData.provider_iban" style="{{ $fieldStyle }}"></div>
                </div>

                <h3 class="text-gray-950 dark:text-white" style="font-size:12px;font-weight:700;margin:0 0 .7rem;text-transform:uppercase;">{{ ($conventionData['agreement_type'] ?? 'service_agreement') === 'copyright_assignment' ? 'Work and rights' : 'Services and payment' }}</h3>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem;">
                    @if(($conventionData['agreement_type'] ?? 'service_agreement') === 'copyright_assignment')
                        <div style="grid-column:1/-1;"><label style="{{ $labelStyle }}">Work description *</label><textarea rows="3" wire:model="conventionData.work_description" style="{{ $fieldStyle }}resize:vertical;"></textarea></div>
                        <div style="grid-column:1/-1;"><label style="{{ $labelStyle }}">Economic rights assigned *</label><textarea rows="3" wire:model="conventionData.rights_scope" style="{{ $fieldStyle }}resize:vertical;"></textarea></div>
                        <div style="grid-column:1/-1;"><label style="{{ $labelStyle }}">Permitted methods of use *</label><textarea rows="2" wire:model="conventionData.use_methods" style="{{ $fieldStyle }}resize:vertical;"></textarea></div>
                        <div><label style="{{ $labelStyle }}">Duration *</label><input wire:model="conventionData.rights_duration" style="{{ $fieldStyle }}"></div>
                        <div><label style="{{ $labelStyle }}">Territory *</label><input wire:model="conventionData.rights_territory" style="{{ $fieldStyle }}"></div>
                        <label style="display:flex;align-items:center;gap:7px;font-size:12px;"><input type="checkbox" wire:model="conventionData.rights_exclusive"> Exclusive assignment</label>
                        <label style="display:flex;align-items:center;gap:7px;font-size:12px;"><input type="checkbox" wire:model="conventionData.right_to_sublicense"> Right to sublicense</label>
                    @else
                        <div style="grid-column:1/-1;"><label style="{{ $labelStyle }}">Service description *</label><textarea rows="3" wire:model="conventionData.service_description" style="{{ $fieldStyle }}resize:vertical;"></textarea></div>
                        <div><label style="{{ $labelStyle }}">Start date *</label><input type="date" wire:model="conventionData.service_start_date" style="{{ $fieldStyle }}"></div>
                        <div><label style="{{ $labelStyle }}">End date *</label><input type="date" wire:model="conventionData.service_end_date" style="{{ $fieldStyle }}"></div>
                        <div><label style="{{ $labelStyle }}">Service location</label><input wire:model="conventionData.service_location" style="{{ $fieldStyle }}"></div>
                    @endif
                    <div style="display:grid;grid-template-columns:1fr .7fr;gap:.5rem;">
                        <div><label style="{{ $labelStyle }}">Gross amount *</label><input type="number" step="0.01" wire:model="conventionData.gross_amount" style="{{ $fieldStyle }}"></div>
                        <div><label style="{{ $labelStyle }}">Currency *</label><input wire:model="conventionData.currency" style="{{ $fieldStyle }}"></div>
                    </div>
                    <div>
                        <label style="{{ $labelStyle }}">Withholding tax</label>
                        <div class="text-gray-700 dark:text-gray-200" style="{{ $fieldStyle }}background:rgba(100,116,139,.06);">{{ number_format((float) $record->withholding_tax_rate, 2) }}% · configured in Project Settings</div>
                    </div>
                    <div><label style="{{ $labelStyle }}">Payment due (days)</label><input type="number" wire:model="conventionData.payment_due_days" style="{{ $fieldStyle }}"></div>
                </div>

                <h3 class="text-gray-950 dark:text-white" style="font-size:12px;font-weight:700;margin:1.3rem 0 .7rem;text-transform:uppercase;">Payment record</h3>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem;">
                    <div><label style="{{ $labelStyle }}">Payment date</label><input type="date" wire:model="conventionData.payment_date" style="{{ $fieldStyle }}"></div>
                    <div><label style="{{ $labelStyle }}">Payment reference</label><input wire:model="conventionData.payment_reference" style="{{ $fieldStyle }}"></div>
                    <div><label style="{{ $labelStyle }}">Payment method</label><select wire:model="conventionData.payment_method" style="{{ $fieldStyle }}">@foreach(\App\Models\Expense::PAYMENT_METHODS as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
                    <div><label style="{{ $labelStyle }}">Payment status</label><select wire:model="conventionData.payment_status" style="{{ $fieldStyle }}">@foreach(\App\Models\Expense::PAYMENT_STATUSES as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
                    <div style="grid-column:1/-1;"><label style="{{ $labelStyle }}">Payment notes</label><textarea rows="2" wire:model="conventionData.payment_notes" style="{{ $fieldStyle }}resize:vertical;"></textarea></div>
                </div>

                @error('conventionData.*') <span style="display:block;color:#dc2626;font-size:11px;margin-top:.7rem;">{{ $message }}</span> @enderror
                <div style="display:flex;justify-content:flex-end;gap:.5rem;margin-top:1.4rem;">
                    <button type="button" wire:click="closeConvention" style="padding:8px 14px;border-radius:7px;border:1px solid rgba(100,116,139,.3);background:transparent;cursor:pointer;">Cancel</button>
                    <button type="button" wire:click="saveConventionDetails" style="padding:8px 14px;border-radius:7px;border:none;background:#4f46e5;color:#fff;cursor:pointer;font-weight:600;">Save details</button>
                </div>
            </div>
        </div>
    @endif

    @if($showDocumentUploadModal)
        <div style="position:fixed;inset:0;z-index:60;background:rgba(15,23,42,.55);display:flex;align-items:center;justify-content:center;padding:1rem;"
             wire:click.self="closeDocumentUpload">
            <div class="fi-section rounded-xl bg-white shadow-xl ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10"
                 style="width:min(520px,100%);padding:1.4rem;">
                <h2 class="text-gray-950 dark:text-white" style="font-size:18px;font-weight:700;margin:0 0 1rem;">Upload project document</h2>

                <label class="text-gray-500 dark:text-gray-400" style="display:block;font-size:11px;font-weight:600;margin-bottom:4px;">TITLE *</label>
                <input type="text" wire:model="documentTitle" style="width:100%;padding:8px 11px;border:1px solid rgba(100,116,139,.3);border-radius:7px;margin-bottom:.8rem;background:transparent;">
                @error('documentTitle') <span style="display:block;color:#dc2626;font-size:11px;margin-top:-8px;margin-bottom:8px;">{{ $message }}</span> @enderror

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem;margin-bottom:.8rem;">
                    <div>
                        <label class="text-gray-500 dark:text-gray-400" style="display:block;font-size:11px;font-weight:600;margin-bottom:4px;">CATEGORY *</label>
                        <select wire:model="documentCategory" style="width:100%;padding:8px 11px;border:1px solid rgba(100,116,139,.3);border-radius:7px;background:transparent;">
                            @foreach($documentCategories as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-gray-500 dark:text-gray-400" style="display:block;font-size:11px;font-weight:600;margin-bottom:4px;">DOCUMENT DATE</label>
                        <input type="date" wire:model="documentDate" style="width:100%;padding:8px 11px;border:1px solid rgba(100,116,139,.3);border-radius:7px;background:transparent;">
                    </div>
                </div>

                <label class="text-gray-500 dark:text-gray-400" style="display:block;font-size:11px;font-weight:600;margin-bottom:4px;">NOTES</label>
                <textarea wire:model="documentNotes" rows="3" style="width:100%;padding:8px 11px;border:1px solid rgba(100,116,139,.3);border-radius:7px;margin-bottom:.8rem;background:transparent;resize:vertical;"></textarea>

                <input type="file" wire:model="documentUpload" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" style="width:100%;font-size:13px;">
                @error('documentUpload') <span style="display:block;color:#dc2626;font-size:11px;margin-top:5px;">{{ $message }}</span> @enderror
                <p class="text-gray-500 dark:text-gray-400" style="font-size:11px;margin:.5rem 0 0;">PDF, image, Word or Excel; maximum 20 MB. Files are stored privately.</p>

                <div style="display:flex;justify-content:flex-end;gap:.5rem;margin-top:1.25rem;">
                    <button type="button" wire:click="closeDocumentUpload" style="padding:8px 14px;border-radius:7px;border:1px solid rgba(100,116,139,.3);background:transparent;cursor:pointer;">Cancel</button>
                    <button type="button" wire:click="uploadProjectDocument" wire:loading.attr="disabled" wire:target="uploadProjectDocument,documentUpload"
                            style="padding:8px 14px;border-radius:7px;border:none;background:#4f46e5;color:#fff;cursor:pointer;font-weight:600;">
                        <span wire:loading.remove wire:target="uploadProjectDocument,documentUpload">Upload</span>
                        <span wire:loading wire:target="uploadProjectDocument,documentUpload">Uploading...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if($showSignedUploadModal)
        <div style="position:fixed;inset:0;z-index:60;background:rgba(15,23,42,.55);display:flex;align-items:center;justify-content:center;padding:1rem;"
             wire:click.self="closeSignedUpload">
            <div class="fi-section rounded-xl bg-white shadow-xl ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10"
                 style="width:min(460px,100%);padding:1.4rem;">
                <h2 class="text-gray-950 dark:text-white" style="font-size:18px;font-weight:700;margin:0 0 .4rem;">Upload signed copy</h2>
                <p class="text-gray-500 dark:text-gray-400" style="font-size:12px;margin:0 0 1rem;">PDF, JPG or PNG, maximum 20 MB. A previous signed copy will be replaced.</p>
                <input type="file" wire:model="signedUpload" accept=".pdf,.jpg,.jpeg,.png" style="width:100%;font-size:13px;">
                @error('signedUpload') <span style="display:block;color:#dc2626;font-size:11px;margin-top:5px;">{{ $message }}</span> @enderror
                <div style="display:flex;justify-content:flex-end;gap:.5rem;margin-top:1.25rem;">
                    <button type="button" wire:click="closeSignedUpload" style="padding:8px 14px;border-radius:7px;border:1px solid rgba(100,116,139,.3);background:transparent;cursor:pointer;">Cancel</button>
                    <button type="button" wire:click="uploadSignedCopy" wire:loading.attr="disabled"
                            style="padding:8px 14px;border-radius:7px;border:none;background:#4f46e5;color:#fff;cursor:pointer;font-weight:600;">Upload</button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
