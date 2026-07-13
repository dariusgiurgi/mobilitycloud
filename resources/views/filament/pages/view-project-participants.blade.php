<x-filament-panels::page>
    <x-ui-polish />
    @if (! $record->isManagementStage())
        @include('filament.pages.partials.project-module-locked', [
            'record' => $record,
            'module' => 'Participants',
            'icon' => 'heroicon-o-users',
            'accent' => '#4f46e5',
            'features' => [
                ['title' => 'Participant register', 'body' => 'Track participant data, partner organisations, roles, countries and fewer-opportunities markers.'],
                ['title' => 'Readiness checks', 'body' => 'See missing GDPR, agreements and parental consent documents before the archive is prepared.'],
                ['title' => 'Attendance sheets', 'body' => 'Generate signature-ready attendance lists grouped by organisation.'],
            ],
        ])
    @else
    @php
        $participants = $this->getParticipants();
        $roles = $this->getRoles();
        $partnerOrgs = $this->getPartnerOrgs();
        $stats = $this->getStats();
        $countriesInUse = $this->getCountriesInUse();
        $orgsInUse = $this->getOrgsInUse();
        $activeFilters = $this->activeParticipantFilters();
        $canManage = $record->canBeManagedBy(auth()->user());
    @endphp

    <style>
        .mc-part-header { display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap; }
        .mc-part-actions { display:flex;align-items:center;gap:.5rem;flex-wrap:wrap; }
        .mc-part-stats { display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem;margin:1rem 0; }
        .mc-part-stat { padding:1rem 1.1rem;border:1px solid rgba(148,163,184,.2);border-radius:.75rem;background:#fff; }
        .mc-part-stat-label { color:#64748b;font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em; }
        .mc-part-stat-value { color:#18181b;font-size:1.3rem;font-weight:700;margin-top:.25rem; }
        .dark .mc-part-stat { background:rgb(17,24,39);border-color:rgba(255,255,255,.1); }
        .dark .mc-part-stat-label { color:#94a3b8; }
        .dark .mc-part-stat-value { color:#f4f4f5; }
        @media (max-width:900px) { .mc-part-stats { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media (max-width:550px) { .mc-part-stats { grid-template-columns:1fr; } }
    </style>

    <div class="mc-part">

    <x-filament::section>
        <div class="mc-part-header">
            <div>
                <div style="display:flex;align-items:center;gap:.45rem;">
                    <h2 class="text-gray-950 dark:text-white" style="font-size:.95rem;font-weight:650;">Participant register</h2>
                    <x-help-tip id="participant-register" title="Participant readiness">
                        A participant is ready when all required documents are uploaded. GDPR consent and the participant agreement are always required; minors also require parental consent.
                    </x-help-tip>
                </div>
                <p class="text-gray-500 dark:text-gray-400" style="font-size:.72rem;margin-top:.2rem;">{{ $stats['organisations'] }} organisations · {{ $stats['fo'] }} participants with fewer opportunities @if(!$canManage) · Read-only access @endif</p>
            </div>
            <div class="mc-part-actions">
                <x-filament::button tag="a" :href="route('projects.export-participants', $record)" color="gray" icon="heroicon-o-arrow-down-tray" size="sm">Export CSV</x-filament::button>
                @if($canManage)
                    <x-filament::button wire:click="openImport" color="gray" icon="heroicon-o-arrow-up-tray" size="sm">Import CSV</x-filament::button>
            <span style="display:inline-flex;align-items:center;gap:.35rem;">
                <x-filament::button wire:click="openAttendanceGenerator" color="gray" icon="heroicon-o-clipboard-document-list" size="sm">Attendance list</x-filament::button>
                <x-help-tip id="participant-attendance-list" title="Attendance list">
                    Generates one landscape PDF grouped by partner organisation. Every organisation starts on a new page; signed copies are stored later in the Documents section.
                </x-help-tip>
            </span>
                    <x-filament::button wire:click="openCreate" icon="heroicon-o-plus" size="sm">Add participant</x-filament::button>
                @endif
            </div>
        </div>
    </x-filament::section>

{{-- Stats --}}
    <div class="mc-part-stats">
        <div class="mc-part-stat">
            <div class="mc-part-stat-label">Total participants</div>
            <div class="mc-part-stat-value">{{ $stats['total'] }}</div>
        </div>
        <div class="mc-part-stat">
            <div class="mc-part-stat-label">Documents ready</div>
            <div class="mc-part-stat-value" style="color:#16a34a;">{{ $stats['complete'] }}</div>
        </div>
        <div class="mc-part-stat">
            <div class="mc-part-stat-label">Documents incomplete</div>
            <div class="mc-part-stat-value" style="color:{{ $stats['incomplete'] > 0 ? '#d97706' : '#16a34a' }};">{{ $stats['incomplete'] }}</div>
        </div>
        <div class="mc-part-stat">
            <div class="mc-part-stat-label">Minors</div>
            <div class="mc-part-stat-value" style="color:{{ $stats['minors'] > 0 ? '#d97706' : 'inherit' }};">{{ $stats['minors'] }}</div>
        </div>
    </div>

    @if($showImportModal)
        <div class="mc-modal-backdrop"
             wire:click.self="$set('showImportModal', false)">
            <div class="mc-part-modal mc-modal-panel"><div class="mc-modal-body">
                <h3 class="mc-modal-heading">Import participants</h3>
                <p class="mc-modal-description">
                    Upload a CSV generated by MobilityCloud. First name and Last name are required. Dates may use YYYY-MM-DD, DD.MM.YYYY or DD/MM/YYYY; Excel date serials are also accepted. The import is limited to 1,000 rows and is cancelled entirely if a row is invalid.
                </p>
                <input type="file" wire:model="importFile" accept=".csv,text/csv" class="text-gray-700 dark:text-gray-200" style="width:100%;font-size:13px;" aria-label="Participants CSV file">
                @error('importFile') <span class="mc-part-err" style="margin-top:.5rem;">{{ $message }}</span> @enderror

                <div class="mc-modal-actions">
                    <button type="button" wire:click="$set('showImportModal', false)"
                            style="padding:8px 16px;border-radius:8px;border:1px solid rgba(100,116,139,.3);background:transparent;cursor:pointer;font-size:13px;">Cancel</button>
                    <button type="button" wire:click="importParticipants" wire:loading.attr="disabled" wire:target="importParticipants,importFile"
                            style="padding:8px 16px;border-radius:8px;border:none;background:#6366f1;color:#fff;cursor:pointer;font-size:13px;font-weight:600;">
                        <span wire:loading.remove wire:target="importParticipants,importFile">Import</span>
                        <span wire:loading wire:target="importParticipants,importFile">Importing...</span>
                    </button>
                </div>
            </div></div>
        </div>
    @endif

    {{-- Search + Filters button --}}
    <div style="display:flex;gap:.6rem;flex-wrap:wrap;align-items:center;margin-bottom:1rem;">
        <input type="text" wire:model.live.debounce.300ms="filterSearch" placeholder="Search name…"
               class="mc-part-in" style="flex:1;min-width:200px;width:auto;background:transparent;">

        <button type="button" wire:click="$toggle('showPartFilters')"
                class="text-gray-700 dark:text-gray-200"
                style="display:inline-flex;align-items:center;gap:7px;padding:8px 14px;border:1px solid rgba(100,116,139,.3);border-radius:8px;background:{{ $showPartFilters ? 'rgba(99,102,241,.08)' : 'transparent' }};cursor:pointer;font-size:13px;font-weight:500;">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
            Filters
            @if($activeFilters > 0)
                <span style="display:inline-flex;align-items:center;justify-content:center;min-width:18px;height:18px;padding:0 5px;border-radius:999px;background:#6366f1;color:#fff;font-size:11px;font-weight:700;">{{ $activeFilters }}</span>
            @endif
        </button>
    </div>

    {{-- Collapsible filters panel --}}
    @if($showPartFilters)
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
         style="padding:1.1rem 1.25rem;margin-bottom:1.25rem;">
        <div style="display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end;">

            <div style="display:flex;flex-direction:column;gap:5px;">
                <label class="text-gray-500 dark:text-gray-400" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Role</label>
                <select wire:model.live="filterRole" class="mc-part-in" style="width:auto;min-width:150px;background:transparent;">
                    <option value="">All roles</option>
                    @foreach($roles as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            @if(count($countriesInUse) > 0)
                <div style="display:flex;flex-direction:column;gap:5px;">
                    <label class="text-gray-500 dark:text-gray-400" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Country</label>
                    <select wire:model.live="filterCountry" class="mc-part-in" style="width:auto;min-width:140px;background:transparent;">
                        <option value="">All countries</option>
                        @foreach($countriesInUse as $c)
                            <option value="{{ $c }}">{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if(count($orgsInUse) > 0)
                <div style="display:flex;flex-direction:column;gap:5px;">
                    <label class="text-gray-500 dark:text-gray-400" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Organisation</label>
                    <select wire:model.live="filterOrg" class="mc-part-in" style="width:auto;min-width:150px;background:transparent;">
                        <option value="">All organisations</option>
                        @foreach($orgsInUse as $o)
                            <option value="{{ $o }}">{{ $o }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <label class="text-gray-700 dark:text-gray-200" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;padding:8px 11px;border:1px solid rgba(100,116,139,.3);border-radius:7px;">
                <input type="checkbox" wire:model.live="filterMinorsOnly" style="accent-color:#d97706;"> Minors only
            </label>

            <label class="text-gray-700 dark:text-gray-200" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;padding:8px 11px;border:1px solid rgba(100,116,139,.3);border-radius:7px;">
                <input type="checkbox" wire:model.live="filterIncompleteOnly" style="accent-color:#dc2626;"> Incomplete only
            </label>

            <div style="flex:1;"></div>

            @if($activeFilters > 0)
                <button type="button" wire:click="clearParticipantFilters"
                        class="text-gray-500 dark:text-gray-400"
                        style="padding:8px 14px;border:1px solid rgba(100,116,139,.3);border-radius:8px;background:transparent;cursor:pointer;font-size:13px;">
                    Clear all
                </button>
            @endif
        </div>
    </div>
    @endif

    

    {{-- List --}}
    @if($participants->isEmpty())
        <div class="mc-empty-state fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="padding:2.5rem;text-align:center;">
            <x-filament::icon icon="heroicon-o-users" class="mx-auto h-10 w-10 text-gray-400" />
            <h3 class="text-gray-950 dark:text-white" style="font-size:1rem;font-weight:750;margin:.65rem 0 .25rem;">{{ $activeFilters > 0 ? 'No participants match these filters' : 'Build the participant register' }}</h3>
            <p class="text-gray-500 dark:text-gray-400" style="font-size:14px;line-height:1.55;margin:0 auto {{ $canManage ? '1rem' : '0' }};max-width:36rem;">
                {{ $activeFilters > 0 ? 'Clear the filters or search by another name, organisation or status.' : 'Add people one by one, import the CSV you exported earlier, then generate attendance sheets when the activity list is ready.' }}
            </p>
            @if($canManage && $activeFilters === 0)
                <div style="display:flex;gap:.55rem;justify-content:center;flex-wrap:wrap;">
                    <x-filament::button wire:click="openCreate" icon="heroicon-o-plus">
                        Add participant
                    </x-filament::button>
                    <x-filament::button wire:click="openImport" color="gray" icon="heroicon-o-arrow-up-tray">
                        Import CSV
                    </x-filament::button>
                    <x-filament::button wire:click="openAttendanceGenerator" color="gray" icon="heroicon-o-clipboard-document-list">
                        Generate attendance list
                    </x-filament::button>
                </div>
            @endif
        </div>
    @else
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="overflow:hidden;">
            <div class="mc-table-scroll" style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:13px;min-width:760px;">
                    <thead>
                        <tr class="text-gray-500 dark:text-gray-400" style="background:rgba(100,116,139,.06);font-size:10px;text-transform:uppercase;letter-spacing:.04em;">
                            <th style="padding:10px 12px;text-align:left;">Name</th>
                            <th style="padding:10px 12px;text-align:left;">Role</th>
                            <th style="padding:10px 12px;text-align:left;">Organisation</th>
                            <th style="padding:10px 12px;text-align:left;">Country</th>
                            <th style="padding:10px 12px;text-align:center;">Age</th>
                            <th style="padding:10px 12px;text-align:center;">Flags</th>
                            <th style="padding:10px 12px;text-align:center;">Documents</th>
                            <th style="padding:10px 12px;text-align:center;width:90px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($participants as $p)
                        <tr wire:key="part-{{ $p->id }}" class="text-gray-950 dark:text-white" style="border-top:1px solid rgba(100,116,139,.12);">
                            <td style="padding:9px 12px;">
                                <div style="font-weight:600;">{{ $p->fullName() }}</div>
                                @if($p->email)<div class="text-gray-400" style="font-size:11px;margin-top:1px;">{{ $p->email }}</div>@endif
                            </td>
                            <td style="padding:9px 12px;" class="text-gray-500 dark:text-gray-400">{{ $p->roleLabel() }}</td>
                            <td style="padding:9px 12px;" class="text-gray-500 dark:text-gray-400">{{ $p->partner_organisation ?: '—' }}</td>
                            <td style="padding:9px 12px;" class="text-gray-500 dark:text-gray-400">{{ $p->country ?: '—' }}</td>
                            <td style="padding:9px 12px;text-align:center;">
                                {{ $p->ageAtReference() ?? '—' }}
                                @if($p->isMinor())
                                    <span title="Minor — needs parental consent" style="display:inline-block;margin-left:4px;font-size:10px;font-weight:700;padding:1px 6px;border-radius:999px;background:rgba(245,158,11,.18);color:#d97706;">MINOR</span>
                                @endif
                            </td>
                            <td style="padding:9px 12px;text-align:center;">
                                @if($p->fewer_opportunities)
                                    <span title="Fewer opportunities" style="font-size:10px;font-weight:700;padding:1px 6px;border-radius:999px;background:rgba(99,102,241,.15);color:#6366f1;">FO</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td style="padding:9px 12px;text-align:center;">
                                @php
                                    $missing = $p->missingDocTypes();
                                    $docLabels = \App\Models\ParticipantAttachment::TYPES;
                                    $missingNames = collect($missing)->map(fn ($t) => $docLabels[$t] ?? $t)->implode(', ');
                                @endphp
                                @if($p->hasCompleteDocs())
                                    <span title="All required documents uploaded"
                                          style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;background:rgba(22,163,74,.15);color:#16a34a;">COMPLETE</span>
                                @else
                                    <span title="Missing: {{ $missingNames }}"
                                          style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;background:rgba(245,158,11,.18);color:#d97706;">{{ count($missing) }} MISSING</span>
                                @endif
                            </td>
                            <td style="padding:9px 12px;text-align:center;">
                                <button type="button" wire:click="openEdit({{ $p->id }})" title="{{ $canManage ? 'Edit' : 'View' }}" aria-label="{{ $canManage ? 'Edit '.$p->fullName() : 'View '.$p->fullName() }}"
                                        style="width:28px;height:28px;border:none;background:transparent;cursor:pointer;color:#9ca3af;border-radius:6px;"
                                        onmouseover="this.style.background='rgba(99,102,241,.1)';this.style.color='#6366f1';"
                                        onmouseout="this.style.background='transparent';this.style.color='#9ca3af';">
                                    @if($canManage)
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline;"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg>
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2.06 12.35a1 1 0 0 1 0-.7C3.42 8.26 7.14 6 12 6s8.58 2.26 9.94 5.65a1 1 0 0 1 0 .7C20.58 15.74 16.86 18 12 18s-8.58-2.26-9.94-5.65Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    @endif
                                </button>
                                @if($canManage)
                                <button type="button" wire:click="deleteParticipant({{ $p->id }})" wire:confirm="Remove this participant?" title="Remove" aria-label="Remove {{ $p->fullName() }}"
                                        style="width:28px;height:28px;border:none;background:transparent;cursor:pointer;color:#9ca3af;border-radius:6px;"
                                        onmouseover="this.style.background='rgba(239,68,68,.1)';this.style.color='#dc2626';"
                                        onmouseout="this.style.background='transparent';this.style.color='#9ca3af';">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline;"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
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

    </div>

    {{-- ── Add/Edit modal ── --}}
    @if($showModal)
    <div class="mc-modal-backdrop mc-modal-top"
         wire:click.self="closeModal">
        <div class="mc-part-modal mc-modal-panel mc-modal-panel-wide"><div class="mc-modal-body">

            <h3 class="mc-modal-heading" style="margin-bottom:1.25rem;">{{ !$canManage ? 'View participant' : ($editingId ? 'Edit participant' : 'Add participant') }}</h3>

            <fieldset @disabled(!$canManage) style="border:0;padding:0;margin:0;min-width:0;">
            {{-- Identity --}}
            <p class="mc-part-sec">Identity</p>
            <div class="mc-part-grid">
                <div>
                    <label class="mc-part-lbl">First name *</label>
                    <input type="text" wire:model="data.first_name" class="mc-part-in" aria-label="Participant first name">
                    @error('data.first_name') <span class="mc-part-err">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mc-part-lbl">Last name *</label>
                    <input type="text" wire:model="data.last_name" class="mc-part-in" aria-label="Participant last name">
                    @error('data.last_name') <span class="mc-part-err">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mc-part-lbl">Birth date</label>
                    <input type="date" wire:model="data.birth_date" class="mc-part-in" aria-label="Participant birth date">
                </div>
                <div>
                    <label class="mc-part-lbl">Gender</label>
                    <select wire:model="data.gender" class="mc-part-in" aria-label="Participant gender">
                        <option value="">—</option>
                        <option value="female">Female</option>
                        <option value="male">Male</option>
                        <option value="other">Other</option>
                        <option value="undisclosed">Prefer not to say</option>
                    </select>
                </div>
                <div>
                    <label class="mc-part-lbl">Nationality</label>
                    <input type="text" wire:model="data.nationality" class="mc-part-in" aria-label="Participant nationality">
                </div>
                <div>
                    <label class="mc-part-lbl">Role</label>
                    <select wire:model="data.role" class="mc-part-in" aria-label="Participant role">
                        @foreach($roles as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Belonging --}}
            <p class="mc-part-sec">Belonging</p>
            <div class="mc-part-grid">
                <div>
                    <label class="mc-part-lbl">Organisation / group</label>
                    @if(count($partnerOrgs) > 0)
                        <select wire:model="data.partner_organisation" class="mc-part-in" aria-label="Participant organisation">
                            <option value="">—</option>
                            @foreach($partnerOrgs as $org)
                                <option value="{{ $org['name'] }}">{{ $org['label'] }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" wire:model="data.partner_organisation" class="mc-part-in" aria-label="Participant organisation"
                               placeholder="Add organisations in the Application first">
                    @endif
                </div>
                <div>
                    <label class="mc-part-lbl">Country</label>
                    <input type="text" wire:model="data.country" class="mc-part-in" aria-label="Participant country">
                </div>
            </div>

            {{-- Contact --}}
            <p class="mc-part-sec">Contact</p>
            <div class="mc-part-grid">
                <div>
                    <label class="mc-part-lbl">Email</label>
                    <input type="email" wire:model="data.email" class="mc-part-in" aria-label="Participant email">
                    @error('data.email') <span class="mc-part-err">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mc-part-lbl">Phone</label>
                    <input type="text" wire:model="data.phone" class="mc-part-in" aria-label="Participant phone">
                </div>
                <div style="grid-column:1 / -1;">
                    <label class="mc-part-lbl">Address</label>
                    <input type="text" wire:model="data.address" class="mc-part-in" aria-label="Participant address">
                </div>
            </div>

            {{-- Sensitive --}}
            <p class="mc-part-sec">Sensitive (GDPR)</p>
            <div class="mc-part-grid">
                <div>
                    <label class="mc-part-lbl">Medical conditions</label>
                    <input type="text" wire:model="data.medical_conditions" class="mc-part-in" aria-label="Participant medical conditions">
                </div>
                <div>
                    <label class="mc-part-lbl">Allergies</label>
                    <input type="text" wire:model="data.allergies" class="mc-part-in" aria-label="Participant allergies">
                </div>
                <div>
                    <label class="mc-part-lbl">Dietary restrictions</label>
                    <input type="text" wire:model="data.dietary_restrictions" class="mc-part-in" aria-label="Participant dietary restrictions">
                </div>
                <div>
                    <label class="mc-part-lbl">Special needs</label>
                    <input type="text" wire:model="data.special_needs" class="mc-part-in" aria-label="Participant special needs">
                </div>
                <div style="grid-column:1 / -1;">
                    <label style="display:inline-flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">
                        <input type="checkbox" wire:model="data.fewer_opportunities" style="accent-color:#6366f1;" aria-label="Participant fewer opportunities">
                        Fewer opportunities
                    </label>
                </div>
            </div>

            {{-- Guardian --}}
            <p class="mc-part-sec">Legal guardian (for minors)</p>
            <div class="mc-part-grid">
                <div>
                    <label class="mc-part-lbl">Guardian name</label>
                    <input type="text" wire:model="data.guardian_name" class="mc-part-in" aria-label="Participant guardian name">
                </div>
                <div>
                    <label class="mc-part-lbl">Guardian contact</label>
                    <input type="text" wire:model="data.guardian_contact" class="mc-part-in" aria-label="Participant guardian contact">
                </div>
            </div>
            </fieldset>

            {{-- Documents (doar la editare, cand participantul exista) --}}
            @if($editingId)
                @php $atts = $this->attachmentsFor($editingId); $docTypes = $this->getDocTypes(); @endphp
                <div style="display:flex;align-items:center;gap:.35rem;">
                    <p class="mc-part-sec">Documents</p>
                    <x-help-tip id="participant-required-documents" title="Required participant documents">
                        GDPR consent and the participant agreement are required for every participant. Parental consent is added automatically when the participant is under 18 on the mobility start date.
                    </x-help-tip>
                </div>

                <div style="display:flex;flex-direction:column;gap:6px;margin-bottom:1rem;">
                    @foreach($docTypes as $typeKey => $typeLabel)
                        @php $att = $atts->get($typeKey); @endphp
                        <div style="display:flex;align-items:center;gap:.6rem;padding:8px 11px;border:1px solid rgba(100,116,139,.2);border-radius:8px;">
                            <span class="text-gray-700 dark:text-gray-200" style="font-size:12px;font-weight:600;min-width:150px;">{{ $typeLabel }}</span>

                            @if($att)
                                <a href="{{ route('attachments.participants.download', $att) }}" target="_blank"
                                   class="text-gray-500 dark:text-gray-400"
                                   style="font-size:12px;text-decoration:none;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                                   title="{{ $att->original_name }}">
                                    📄 {{ \Illuminate\Support\Str::afterLast($att->path, '/') }} <span style="opacity:.6;">({{ $att->humanSize() }})</span>
                                </a>
                                @if($canManage)
                                <button type="button" wire:click="deleteAttachment({{ $att->id }})"
                                        title="Remove" style="border:none;background:transparent;cursor:pointer;color:#9ca3af;font-size:14px;"
                                        onmouseover="this.style.color='#dc2626';" onmouseout="this.style.color='#9ca3af';">✕</button>
                                @endif
                            @else
                                <span class="text-gray-400" style="font-size:12px;flex:1;">— not uploaded</span>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if($canManage)
                {{-- Upload row --}}
                <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;padding:10px 11px;border:1px dashed rgba(100,116,139,.35);border-radius:8px;margin-bottom:.5rem;"
                     wire:key="upload-row-{{ $editingId }}">
                    <select wire:model="uploadType" class="mc-part-in" style="width:auto;min-width:160px;">
                        @foreach($docTypes as $typeKey => $typeLabel)
                            <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                        @endforeach
                    </select>

                    <input type="file" wire:model="uploadFile"
                           class="text-gray-600 dark:text-gray-300" style="font-size:12px;flex:1;min-width:160px;">

                    <button type="button" wire:click="uploadAttachment"
                            wire:loading.attr="disabled" wire:target="uploadAttachment,uploadFile"
                            style="padding:7px 14px;border-radius:7px;border:none;background:#6366f1;color:#fff;cursor:pointer;font-size:12px;font-weight:600;">
                        <span wire:loading.remove wire:target="uploadAttachment,uploadFile">Upload</span>
                        <span wire:loading wire:target="uploadAttachment,uploadFile">…</span>
                    </button>
                </div>
                @error('uploadFile') <span class="mc-part-err" style="margin-bottom:.5rem;">{{ $message }}</span> @enderror
                <p class="text-gray-400" style="font-size:11px;margin:0 0 .5rem;">PDF, JPG, PNG, DOC or DOCX; max 10 MB. Uploading the same type replaces the previous file.</p>
                @endif
            @else
                <p class="mc-part-sec">Documents</p>
                <p class="text-gray-400" style="font-size:12px;margin-bottom:1rem;">Save the participant first, then reopen to attach documents.</p>
            @endif

            <div class="mc-modal-actions" style="margin-top:1.5rem;">
                <button type="button" wire:click="closeModal"
                        style="padding:9px 18px;border-radius:8px;border:1px solid rgba(100,116,139,.3);background:transparent;cursor:pointer;font-size:13px;">{{ $canManage ? 'Cancel' : 'Close' }}</button>
                @if($canManage)
                <button type="button" wire:click="save"
                        style="padding:9px 18px;border-radius:8px;border:none;background:#6366f1;color:#fff;cursor:pointer;font-size:13px;font-weight:600;">Save</button>
                @endif
            </div>
        </div></div>
    </div>
    @endif

    <style>
        .mc-part-sec { font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#71717a;margin:1.25rem 0 .6rem; }
        .mc-part-sec:first-of-type { margin-top:0; }
        .mc-part-grid { display:grid;grid-template-columns:1fr 1fr;gap:.85rem; }
        .mc-part-lbl { display:block;font-size:11px;font-weight:600;color:#71717a;margin-bottom:4px; }
        .mc-part-in { width:100%;padding:8px 11px;border:1px solid rgba(100,116,139,.3);border-radius:7px;background:#fafafa;color:#18181b;font-size:13px; }
        .mc-part-err { display:block;color:#dc2626;font-size:11px;margin-top:3px; }

        .dark .mc-part-modal { background:#18212f !important; color:#f4f4f5 !important; }
        .dark .mc-part-sec { color:#a1a1aa; }
        .dark .mc-part-lbl { color:#a1a1aa; }
        .dark .mc-part-in { background:#27303f !important; color:#f4f4f5 !important; border-color:rgba(148,163,184,.3) !important; }
        .dark .mc-part-in option { background:#27303f; color:#f4f4f5; }
    </style>
    @include('filament.partials.attendance-generator-modal')
    @endif
</x-filament-panels::page>
