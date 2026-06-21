<x-filament-panels::page>
    @php
        $documents = $this->getDocuments();
        $documentCategories = $this->getDocumentCategories();
        $civilConventions = $this->getCivilConventionExpenses();
    @endphp

    <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;flex-wrap:wrap;">
        <p class="text-gray-500 dark:text-gray-400" style="font-size:13px;margin:0;">The project's private document repository and generated attendance records.</p>
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
        @endif
    </div>

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
                    <div
                            class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
                            style="width:100%;padding:.9rem 1.1rem;display:flex;align-items:center;gap:1rem;text-align:left;">
                        <div style="font-size:22px;">📝</div>
                        <div style="flex:1;min-width:180px;">
                            <div class="text-gray-950 dark:text-white" style="font-size:13px;font-weight:700;">{{ $expense->description ?: 'Untitled civil convention' }}</div>
                            <div class="text-gray-500 dark:text-gray-400" style="font-size:11px;margin-top:3px;">
                                {{ $this->expenseCode($expense) }} · {{ $expense->budgetLine?->title }} · {{ number_format((float) $expense->amount, 2) }} {{ $expense->currency }}
                            </div>
                        </div>
                        <span style="padding:4px 9px;border-radius:999px;font-size:10px;font-weight:700;background:{{ $expense->hasCompleteConventionData() ? '#dcfce7' : '#fef3c7' }};color:{{ $expense->hasCompleteConventionData() ? '#166534' : '#92400e' }};">
                            {{ $expense->hasCompleteConventionData() ? 'READY' : 'DETAILS NEEDED' }}
                        </span>
                        @if($expense->hasCompleteConventionData())
                            <a href="{{ route('project-documents.civil-convention', [$record, $expense]) }}"
                               style="padding:7px 11px;border-radius:7px;border:1px solid rgba(79,70,229,.3);color:#4338ca;text-decoration:none;font-size:12px;font-weight:600;">
                                Generate PDF
                            </a>
                        @endif
                        @if($expense->hasCompleteConventionData() && $expense->hasCompleteAcceptanceData())
                            <a href="{{ route('project-documents.acceptance-certificate', [$record, $expense]) }}"
                               style="padding:7px 11px;border-radius:7px;border:1px solid rgba(34,197,94,.35);color:#15803d;text-decoration:none;font-size:12px;font-weight:600;">
                                Acceptance PDF
                            </a>
                        @endif
                        @if($record->canBeManagedBy(auth()->user()))
                            <button type="button" wire:click="openConvention({{ $expense->id }})"
                                    style="padding:7px 11px;border-radius:7px;border:none;background:#eef2ff;color:#4338ca;cursor:pointer;font-size:12px;">
                                {{ $expense->hasCompleteConventionData() ? 'Edit details' : 'Complete details' }}
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <h2 class="text-gray-950 dark:text-white" style="font-size:15px;font-weight:700;margin:0 0 .65rem;">Project files and generated documents</h2>

    @if($documents->isEmpty())
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
             style="padding:2.5rem;text-align:center;">
            <div style="font-size:34px;margin-bottom:.6rem;">📄</div>
            <h3 class="text-gray-950 dark:text-white" style="font-size:16px;font-weight:700;margin:0 0 .35rem;">No project documents yet</h3>
            <p class="text-gray-500 dark:text-gray-400" style="font-size:13px;margin:0;">Upload a project file or generate the first attendance list.</p>
        </div>
    @else
        <div style="display:flex;flex-direction:column;gap:.75rem;">
            @foreach($documents as $document)
                <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
                     style="padding:1rem 1.1rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
                    <div style="font-size:24px;">{{ $document->type === \App\Models\ProjectDocument::TYPE_ATTENDANCE ? '📋' : '📄' }}</div>
                    <div style="flex:1;min-width:220px;">
                        <div class="text-gray-950 dark:text-white" style="font-size:14px;font-weight:700;">{{ $document->title }}</div>
                        @if($document->type === \App\Models\ProjectDocument::TYPE_ATTENDANCE)
                            <div class="text-gray-500 dark:text-gray-400" style="font-size:11px;margin-top:3px;">
                                Attendance · {{ $document->activity_date?->format('d M Y') }}
                                @if($document->location) · {{ $document->location }} @endif
                                · Generated {{ $document->generated_at?->format('d M Y, H:i') }}
                            </div>
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

                    @if($document->type === \App\Models\ProjectDocument::TYPE_ATTENDANCE)
                        <span style="padding:4px 9px;border-radius:999px;font-size:11px;font-weight:700;background:{{ $document->hasSignedCopy() ? '#dcfce7' : '#fef3c7' }};color:{{ $document->hasSignedCopy() ? '#166534' : '#92400e' }};">
                            {{ $document->statusLabel() }}
                        </span>

                        <a href="{{ route('project-documents.attendance', [$record, $document]) }}"
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

                <h3 class="text-gray-950 dark:text-white" style="font-size:12px;font-weight:700;margin:1.3rem 0 .7rem;text-transform:uppercase;">Acceptance record</h3>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem;">
                    <div><label style="{{ $labelStyle }}">Acceptance date</label><input type="date" wire:model="conventionData.acceptance_date" style="{{ $fieldStyle }}"></div>
                    <div><label style="{{ $labelStyle }}">Acceptance place</label><input wire:model="conventionData.acceptance_place" style="{{ $fieldStyle }}"></div>
                    <div style="grid-column:1/-1;"><label style="{{ $labelStyle }}">Delivered services / work</label><textarea rows="3" wire:model="conventionData.acceptance_deliverables" style="{{ $fieldStyle }}resize:vertical;"></textarea></div>
                    <div style="grid-column:1/-1;"><label style="{{ $labelStyle }}">Acceptance status</label><select wire:model="conventionData.acceptance_status" style="{{ $fieldStyle }}">@foreach(\App\Models\Expense::ACCEPTANCE_STATUSES as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
                    <div style="grid-column:1/-1;"><label style="{{ $labelStyle }}">Observations / reservations</label><textarea rows="2" wire:model="conventionData.acceptance_notes" style="{{ $fieldStyle }}resize:vertical;"></textarea></div>
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
