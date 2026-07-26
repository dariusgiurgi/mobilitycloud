<x-filament-panels::page>
    <x-ui-polish />
    @if (! $record->implementationModulesAvailable())
        @include('filament.pages.partials.project-module-locked', [
            'record' => $record,
            'module' => 'Mobility',
            'icon' => 'heroicon-o-map',
            'accent' => '#0f766e',
            'features' => [
                ['title' => 'Evidence by day', 'body' => 'Organise photos, links and files around the actual daily programme.'],
                ['title' => 'Materials & outputs', 'body' => 'Keep files created for participants and participant outputs in one place.'],
                ['title' => 'Dissemination reports', 'body' => 'Collect one report and evidence files for every organisation involved in dissemination.'],
                ['title' => 'Mobility report', 'body' => 'Write the implementation narrative after the evidence is organised.'],
            ],
        ])
    @else
    @php
        $summary = $this->getMobilitySummary();
        $documents = $this->getMobilityDocuments();
        $materialDocuments = $this->getMaterialOutputDocuments();
        $categories = $this->getMobilityCategories();
        $materialCategories = collect($categories)->except('mobility_photo_video')->all();
        $disseminationOrganisations = $this->getDisseminationOrganisations();
        $disseminationEvidence = $this->getDisseminationEvidenceByOrganisation();
        $disseminationSummary = $this->getDisseminationSummary();
        $evidenceDays = $this->getEvidenceDays();
        $storedEvidenceDays = $this->evidenceDays;
        $evidenceDocuments = $this->getEvidenceDocumentsByDay();
        $canManage = $record->canManageProjectModule(auth()->user(), 'mobility');
        $mobilityLocks = $this->projectLocksForModule('mobility');
    @endphp

    <x-filament::section>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
            <div style="min-width:240px;flex:1;">
                <h2 class="text-gray-950 dark:text-white" style="font-size:1rem;font-weight:750;margin:0;">Mobility workspace</h2>
                <p class="text-gray-500 dark:text-gray-400" style="font-size:.8rem;margin:.18rem 0 0;line-height:1.45;">Start from day-by-day evidence, then organise materials, dissemination and the final mobility narrative.</p>
            </div>
        </div>
    </x-filament::section>

    @include('filament.pages.partials.project-collaboration-strip', ['module' => 'mobility'])

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.65rem;margin-top:.8rem;">
        @foreach([
            ['label' => 'Mobility files', 'value' => $summary['files'], 'color' => '#4f46e5'],
            ['label' => 'Materials', 'value' => $summary['materials'], 'color' => '#7c3aed'],
            ['label' => 'Outputs', 'value' => $summary['outputs'], 'color' => '#059669'],
            ['label' => 'Evidence days', 'value' => $summary['evidence_days'], 'color' => '#2563eb'],
            ['label' => 'Evidence files', 'value' => $summary['evidence_files'], 'color' => '#0f766e'],
            ['label' => 'Dissemination', 'value' => $disseminationSummary['with_reports'].'/'.$disseminationSummary['organisations'], 'color' => $disseminationSummary['complete'] ? '#059669' : '#d97706'],
        ] as $stat)
            <div class="bg-white dark:bg-gray-900" style="padding:.8rem .9rem;border:1px solid rgba(148,163,184,.22);border-radius:.85rem;">
                <p class="text-gray-400" style="font-size:.58rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;">{{ $stat['label'] }}</p>
                <p style="font-size:1.25rem;font-weight:850;margin-top:.2rem;color:{{ $stat['color'] }};">{{ $stat['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-top:1rem;">
        <x-filament::tabs label="Mobility sections">
            <x-filament::tabs.item wire:click="setMobilityTab('evidences')" :active="$activeMobilityTab === 'evidences'" icon="heroicon-m-camera" :badge="$summary['evidence_days'] ?: null">
                Evidences
            </x-filament::tabs.item>
            <x-filament::tabs.item wire:click="setMobilityTab('materials')" :active="$activeMobilityTab === 'materials'" icon="heroicon-m-folder" :badge="$materialDocuments->count() ?: null">
                Materials & Outputs
            </x-filament::tabs.item>
            <x-filament::tabs.item wire:click="setMobilityTab('dissemination')" :active="$activeMobilityTab === 'dissemination'" icon="heroicon-m-megaphone" :badge="$disseminationSummary['missing'] ?: null" :badge-color="$disseminationSummary['complete'] ? 'success' : 'warning'">
                Dissemination
            </x-filament::tabs.item>
            <x-filament::tabs.item wire:click="setMobilityTab('reports')" :active="$activeMobilityTab === 'reports'" icon="heroicon-m-clipboard-document-check" :badge="$summary['report_ready'] ? null : '!' " :badge-color="$summary['report_ready'] ? 'success' : 'warning'">
                Mobility
            </x-filament::tabs.item>
        </x-filament::tabs>
    </div>

    @if($activeMobilityTab === 'reports')
        @php
            $reportLock = $mobilityLocks->get('mobility-report');
            $reportLockedByOther = $reportLock && (int) $reportLock->user_id !== (int) auth()->id();
            $reportBadge = $reportLock ? $this->projectLockBadge($reportLock) : null;
            $canManageReport = $canManage && ! $reportLockedByOther;
        @endphp
        <div style="display:grid;grid-template-columns:minmax(0,1fr) minmax(280px,.42fr);gap:1rem;margin-top:1rem;align-items:start;">
            <div class="mc-lock-frame"
                 @if($reportLock && (int) $reportLock->user_id === (int) auth()->id()) wire:click.outside="stopProjectEditing('mobility', 'mobility-report')" @endif
                 style="{{ $this->projectLockFrameStyle($reportLock, 'transparent', 'border-radius:1rem;') }}">
            <x-filament::section heading="Mobility implementation report" description="Use this for the main internal report about what happened during the mobility." icon="heroicon-o-clipboard-document-check">
                @if($reportBadge)
                    @include('filament.pages.partials.project-lock-badge', [
                        'badge' => $reportBadge,
                        'text' => $reportLockedByOther ? $reportBadge['name'].' edits this report' : 'You are editing this report',
                    ])
                @endif
                <textarea rows="10" wire:model.defer="mobilityReport"
                          wire:focus="startProjectEditing('mobility', 'mobility-report', 'Mobility report')"
                          aria-label="Mobility implementation report"
                          placeholder="Describe the mobility implementation: what was delivered, materials created, participant outputs, unexpected changes, learning moments and evidence kept for the final report."
                          @readonly(!$canManageReport)
                          style="width:100%;padding:.75rem .85rem;border:1px solid rgba(100,116,139,.28);border-radius:.75rem;background:transparent;font-size:.82rem;resize:vertical;"></textarea>
                @error('mobilityReport') <span style="display:block;color:#dc2626;font-size:11px;margin-top:5px;">{{ $message }}</span> @enderror

                @if($canManageReport)
                    <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;margin-top:.75rem;">
                        <x-filament::button wire:click="saveMobilityReport" icon="heroicon-m-check">
                            Save report
                        </x-filament::button>
                        <x-filament::badge :color="$summary['report_ready'] ? 'success' : 'warning'">
                            {{ $summary['report_ready'] ? 'Report saved' : 'Report not filled' }}
                        </x-filament::badge>
                    </div>
                @endif
            </x-filament::section>
            </div>

            <x-filament::section heading="Report structure" description="A practical structure testers can understand quickly." icon="heroicon-o-list-bullet">
                <div style="display:grid;gap:.65rem;">
                    @foreach([
                        ['title' => '1. What happened', 'body' => 'Activities delivered, location, dates and participants involved.'],
                        ['title' => '2. What was produced', 'body' => 'Materials, worksheets, participant outputs and visible outcomes.'],
                        ['title' => '3. What changed', 'body' => 'Learning, unexpected changes, improvements and issues.'],
                        ['title' => '4. Where is the proof', 'body' => 'Use Evidences for day-by-day photos, links and files.'],
                    ] as $item)
                        <div style="border:1px solid rgba(148,163,184,.2);border-radius:.75rem;padding:.75rem;">
                            <div class="text-gray-950 dark:text-white" style="font-size:.78rem;font-weight:800;">{{ $item['title'] }}</div>
                            <p class="text-gray-500 dark:text-gray-400" style="font-size:.7rem;line-height:1.45;margin:.16rem 0 0;">{{ $item['body'] }}</p>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        </div>
    @endif

    @if($activeMobilityTab === 'dissemination')
        <x-filament::section heading="Dissemination reports by organisation" description="Each organisation should have a short report and attached visual/documentary evidence." icon="heroicon-o-megaphone" style="margin-top:1rem;">
            <div style="display:flex;align-items:center;gap:.45rem;flex-wrap:wrap;margin-bottom:.85rem;">
                <x-filament::badge :color="$disseminationSummary['complete'] ? 'success' : 'warning'">
                    {{ $disseminationSummary['with_reports'] }} / {{ $disseminationSummary['organisations'] }} reports
                </x-filament::badge>
                <x-filament::badge :color="$disseminationSummary['with_evidence'] === $disseminationSummary['organisations'] ? 'success' : 'warning'">
                    {{ $disseminationSummary['with_evidence'] }} / {{ $disseminationSummary['organisations'] }} with evidence
                </x-filament::badge>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(330px,1fr));gap:.75rem;">
                @foreach($disseminationOrganisations as $organisation)
                    @php
                        $orgEvidence = $disseminationEvidence[$organisation['key']] ?? collect();
                        $orgImages = $orgEvidence->filter(fn ($evidence) => $evidence->isImageFile())->values();
                        $orgFiles = $orgEvidence->reject(fn ($evidence) => $evidence->isImageFile())->values();
                        $orgLock = $mobilityLocks->get('dissemination:'.$organisation['key']);
                        $orgLockedByOther = $orgLock && (int) $orgLock->user_id !== (int) auth()->id();
                        $orgBadge = $orgLock ? $this->projectLockBadge($orgLock) : null;
                        $canManageOrg = $canManage && ! $orgLockedByOther;
                    @endphp
                    <div class="mc-lock-frame bg-white dark:bg-gray-900"
                         @if($orgLock && (int) $orgLock->user_id === (int) auth()->id()) wire:click.outside="stopProjectEditing('mobility', 'dissemination:{{ $organisation['key'] }}')" @endif
                         style="{{ $this->projectLockFrameStyle($orgLock, 'rgba(148,163,184,.22)', 'border-radius:.9rem;padding:.85rem;') }}">
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.75rem;flex-wrap:wrap;">
                            <div>
                                <h3 class="text-gray-950 dark:text-white" style="font-size:.86rem;font-weight:800;margin:0;">{{ $organisation['name'] }}</h3>
                                <p class="text-gray-500 dark:text-gray-400" style="font-size:.66rem;margin:.12rem 0 0;">
                                    {{ $organisation['country'] ?: 'Country not set' }} @if($organisation['oid']) · OID {{ $organisation['oid'] }} @endif
                                </p>
                            </div>
                            <x-filament::badge :color="$orgEvidence->isNotEmpty() ? 'success' : 'warning'">{{ $orgEvidence->count() }} evidence files</x-filament::badge>
                        </div>
                        @if($orgBadge)
                            @include('filament.pages.partials.project-lock-badge', [
                                'badge' => $orgBadge,
                                'text' => $orgLockedByOther ? $orgBadge['name'].' edits this report' : 'You edit this report',
                            ])
                        @endif

                        <textarea rows="4" wire:model.defer="disseminationReports.{{ $organisation['key'] }}"
                                  wire:focus="startProjectEditing('mobility', 'dissemination:{{ $organisation['key'] }}', 'Dissemination: {{ addslashes($organisation['name']) }}')"
                                  placeholder="Describe this organisation's dissemination: activity, date, audience, channels, number of people reached, links and results."
                                  @readonly(!$canManageOrg)
                                  style="width:100%;margin-top:.65rem;padding:.65rem .75rem;border:1px solid rgba(100,116,139,.25);border-radius:.7rem;background:transparent;font-size:.78rem;resize:vertical;"></textarea>
                        @error('disseminationReports.'.$organisation['key']) <span style="display:block;color:#dc2626;font-size:11px;margin-top:5px;">{{ $message }}</span> @enderror

                        @if($canManageOrg)
                            <div style="display:flex;gap:.45rem;align-items:center;flex-wrap:wrap;margin-top:.6rem;">
                                <x-filament::button wire:click="saveDisseminationReport('{{ $organisation['key'] }}')" size="sm" icon="heroicon-m-check">
                                    Save report
                                </x-filament::button>
                                <x-filament::button wire:click="prepareDisseminationUpload('{{ $organisation['key'] }}')" color="gray" size="sm" icon="heroicon-m-arrow-up-tray">
                                    Prepare upload
                                </x-filament::button>
                            </div>

                            @if($disseminationUploadOrgKey === $organisation['key'])
                                <div style="margin-top:.65rem;padding:.7rem;border:1px dashed rgba(99,102,241,.35);border-radius:.75rem;background:rgba(99,102,241,.045);display:grid;gap:.55rem;">
                                    <input type="text" wire:model="disseminationUploadTitle" aria-label="Dissemination evidence title" style="width:100%;padding:.58rem .68rem;border:1px solid rgba(100,116,139,.25);border-radius:.6rem;background:transparent;">
                                    <input type="date" wire:model="disseminationUploadDate" style="width:100%;padding:.58rem .68rem;border:1px solid rgba(100,116,139,.25);border-radius:.6rem;background:transparent;">
                                    <input type="file" wire:model="disseminationUploads" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx" aria-label="Upload dissemination evidence" style="font-size:.76rem;">
                                    @error('disseminationUploads') <span style="display:block;color:#dc2626;font-size:11px;">{{ $message }}</span> @enderror
                                    @error('disseminationUploads.*') <span style="display:block;color:#dc2626;font-size:11px;">{{ $message }}</span> @enderror
                                    <x-filament::button wire:click="uploadDisseminationEvidence('{{ $organisation['key'] }}')" wire:loading.attr="disabled" wire:target="uploadDisseminationEvidence,disseminationUploads" size="sm" icon="heroicon-m-arrow-up-tray">
                                        Upload selected evidence
                                    </x-filament::button>
                                </div>
                            @endif
                        @endif

                        @if($orgEvidence->isNotEmpty())
                            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(145px,1fr));gap:.6rem;margin-top:.75rem;">
                                @foreach($orgEvidence->take(12) as $evidence)
                                    @include('filament.pages.partials.project-file-card', [
                                        'record' => $record,
                                        'document' => $evidence,
                                        'compact' => true,
                                        'showDelete' => false,
                                    ])
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif

    @if($activeMobilityTab === 'materials')
        <div style="display:grid;grid-template-columns:minmax(0,1fr) minmax(320px,.62fr);gap:1rem;margin-top:1rem;align-items:start;">
            <x-filament::section heading="Materials & Outputs" description="Files created for participants or produced during the mobility." style="margin-top:0;">
                <div style="display:flex;align-items:center;justify-content:flex-end;gap:.65rem;flex-wrap:wrap;margin-bottom:.85rem;">
                    <div style="width:min(280px,100%);">
                        <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
                            <x-filament::input type="search" wire:model.live.debounce.300ms="documentSearch" placeholder="Search materials" />
                        </x-filament::input.wrapper>
                    </div>
                    <div style="width:210px;">
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model.live="categoryFilter">
                                <option value="">All categories</option>
                                @foreach($materialCategories as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>
                </div>

                @if($materialDocuments->isNotEmpty())
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(165px,1fr));gap:.75rem;">
                        @foreach($materialDocuments as $document)
                            @include('filament.pages.partials.project-file-card', [
                                'record' => $record,
                                'document' => $document,
                                'deleteMethod' => 'deleteMobilityDocument',
                                'deleteConfirm' => 'Delete this mobility file?',
                            ])
                        @endforeach
                    </div>
                @else
                    <div class="mc-empty-state fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="padding:2rem;text-align:center;">
                        <x-filament::icon icon="heroicon-o-folder-open" class="mx-auto h-10 w-10 text-gray-400" />
                        <h3 class="text-gray-950 dark:text-white" style="font-size:1rem;font-weight:750;margin:.5rem 0 .25rem;">No materials uploaded yet</h3>
                        <p class="text-gray-500 dark:text-gray-400" style="font-size:.8rem;line-height:1.55;margin:0 auto;max-width:34rem;">Upload agendas, worksheets, participant outputs, presentations or certificates.</p>
                    </div>
                @endif
            </x-filament::section>

            @if($canManage)
                @php
                    $materialsLock = $mobilityLocks->get('materials');
                    $materialsLockedByOther = $materialsLock && (int) $materialsLock->user_id !== (int) auth()->id();
                    $materialsBadge = $materialsLock ? $this->projectLockBadge($materialsLock) : null;
                    $canManageMaterials = $canManage && ! $materialsLockedByOther;
                @endphp
                <div class="mc-lock-frame"
                     @if($materialsLock && (int) $materialsLock->user_id === (int) auth()->id()) wire:click.outside="stopProjectEditing('mobility', 'materials')" @endif
                     style="{{ $this->projectLockFrameStyle($materialsLock, 'transparent', 'border-radius:1rem;') }}">
                <x-filament::section heading="Upload material or output" description="Use this for agendas, worksheets, participant outputs, certificates, presentations or other implementation files." icon="heroicon-o-arrow-up-tray">
                    @if($materialsBadge)
                        @include('filament.pages.partials.project-lock-badge', [
                            'badge' => $materialsBadge,
                            'text' => $materialsLockedByOther ? $materialsBadge['name'].' uploads materials' : 'You upload materials',
                        ])
                    @endif
                    <div style="display:grid;gap:.65rem;">
                        <div>
                            <label class="text-gray-500 dark:text-gray-400" style="display:block;font-size:.62rem;font-weight:750;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.25rem;">Title *</label>
                            <input type="text" wire:focus="startProjectEditing('mobility', 'materials', 'Materials & outputs')" wire:model="documentTitle" aria-label="Mobility document title" @disabled(!$canManageMaterials) style="width:100%;padding:.62rem .72rem;border:1px solid rgba(100,116,139,.28);border-radius:.65rem;background:transparent;">
                            @error('documentTitle') <span style="display:block;color:#dc2626;font-size:11px;margin-top:5px;">{{ $message }}</span> @enderror
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.55rem;">
                            <div>
                                <label class="text-gray-500 dark:text-gray-400" style="display:block;font-size:.62rem;font-weight:750;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.25rem;">Category *</label>
                                <select wire:focus="startProjectEditing('mobility', 'materials', 'Materials & outputs')" wire:model="documentCategory" aria-label="Mobility document category" @disabled(!$canManageMaterials) style="width:100%;padding:.62rem .72rem;border:1px solid rgba(100,116,139,.28);border-radius:.65rem;background:transparent;">
                                    @foreach($materialCategories as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="text-gray-500 dark:text-gray-400" style="display:block;font-size:.62rem;font-weight:750;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.25rem;">Date</label>
                                <input type="date" wire:focus="startProjectEditing('mobility', 'materials', 'Materials & outputs')" wire:model="documentDate" @disabled(!$canManageMaterials) style="width:100%;padding:.62rem .72rem;border:1px solid rgba(100,116,139,.28);border-radius:.65rem;background:transparent;">
                            </div>
                        </div>

                        <div>
                            <label class="text-gray-500 dark:text-gray-400" style="display:block;font-size:.62rem;font-weight:750;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.25rem;">Notes</label>
                            <textarea rows="3" wire:focus="startProjectEditing('mobility', 'materials', 'Materials & outputs')" wire:model="documentNotes" aria-label="Mobility document notes" @readonly(!$canManageMaterials) style="width:100%;padding:.62rem .72rem;border:1px solid rgba(100,116,139,.28);border-radius:.65rem;background:transparent;resize:vertical;"></textarea>
                        </div>

                        <div>
                            <label class="text-gray-500 dark:text-gray-400" style="display:block;font-size:.62rem;font-weight:750;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.25rem;">File *</label>
                            <input type="file" wire:focus="startProjectEditing('mobility', 'materials', 'Materials & outputs')" wire:model="documentUpload" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip" aria-label="Mobility document file" @disabled(!$canManageMaterials) style="width:100%;font-size:.78rem;">
                            @error('documentUpload') <span style="display:block;color:#dc2626;font-size:11px;margin-top:5px;">{{ $message }}</span> @enderror
                        </div>

                        <x-filament::button wire:click="uploadMobilityDocument" wire:loading.attr="disabled" wire:target="uploadMobilityDocument,documentUpload" icon="heroicon-m-arrow-up-tray" :disabled="! $canManageMaterials">
                            <span wire:loading.remove wire:target="uploadMobilityDocument,documentUpload">Upload document</span>
                            <span wire:loading wire:target="uploadMobilityDocument,documentUpload">Uploading...</span>
                        </x-filament::button>
                    </div>
                </x-filament::section>
                </div>
            @endif
        </div>
    @endif

    @if($activeMobilityTab === 'evidences')
        <div style="display:grid;grid-template-columns:minmax(0,1fr) minmax(300px,.45fr);gap:1rem;margin-top:1rem;align-items:start;">
            <div style="display:grid;gap:.85rem;">
                <x-filament::section heading="Evidence by day" description="Create one card for each mobility day, then attach photos, links and participant/presentation files to that day." icon="heroicon-o-calendar-days">
                    @if($canManage && $evidenceDays !== [])
                        <div style="display:flex;justify-content:flex-end;margin-bottom:.75rem;">
                            <x-filament::button wire:click="addEvidenceDay" icon="heroicon-m-plus">
                                Add day
                            </x-filament::button>
                        </div>
                    @endif

                    @if($evidenceDays === [])
                        <div class="mc-empty-state" style="padding:2rem;text-align:center;border:1px dashed rgba(148,163,184,.3);border-radius:.9rem;">
                            <x-filament::icon icon="heroicon-o-camera" class="mx-auto h-10 w-10 text-gray-400" />
                            <h3 class="text-gray-950 dark:text-white" style="font-size:1rem;font-weight:750;margin:.5rem 0 .25rem;">Start with the first mobility day</h3>
                            <p class="text-gray-500 dark:text-gray-400" style="font-size:.8rem;line-height:1.55;margin:0 auto .9rem;max-width:34rem;">Each day can have its own title, date, narrative, observations, links, photos and files.</p>
                            @if($canManage)
                                <x-filament::button wire:click="addEvidenceDay" icon="heroicon-m-plus">Add day</x-filament::button>
                            @endif
                        </div>
                    @endif

                    <div style="display:grid;gap:.8rem;">
                        @foreach($evidenceDays as $day)
                            @php
                                $dayDocuments = $evidenceDocuments[$day['id']] ?? ['images' => collect(), 'files' => collect()];
                                $dayImages = $dayDocuments['images'] ?? collect();
                                $dayFiles = $dayDocuments['files'] ?? collect();
                                $dayLinks = $storedEvidenceDays[$day['id']]['links'] ?? [];
                                $isDayOpen = (bool) ($openEvidenceDays[$day['id']] ?? false) || in_array($evidenceUploadDayId, [$day['id'].'_images', $day['id'].'_files'], true);
                                $dayLock = $mobilityLocks->get('evidence-day:'.$day['id']);
                                $dayLockedByOther = $dayLock && (int) $dayLock->user_id !== (int) auth()->id();
                                $dayBadge = $dayLock ? $this->projectLockBadge($dayLock) : null;
                                $canManageDay = $canManage && ! $dayLockedByOther;
                            @endphp
                            <div wire:key="evidence-day-{{ $day['id'] }}"
                                 @if($dayLock && (int) $dayLock->user_id === (int) auth()->id()) wire:click.outside="stopProjectEditing('mobility', 'evidence-day:{{ $day['id'] }}')" @endif
                                 class="mc-lock-frame bg-white dark:bg-gray-900"
                                 style="{{ $this->projectLockFrameStyle($dayLock, 'rgba(148,163,184,.22)', 'border-radius:1rem;padding:.15rem .15rem .2rem;') }}">
                                @if($dayBadge)
                                    @include('filament.pages.partials.project-lock-badge', [
                                        'badge' => $dayBadge,
                                        'text' => $dayLockedByOther ? $dayBadge['name'].' edits this day' : 'You edit this day',
                                    ])
                                @endif

                                <button type="button" wire:click="toggleEvidenceDay('{{ $day['id'] }}')" style="width:100%;display:flex;align-items:center;justify-content:space-between;gap:.75rem;flex-wrap:wrap;cursor:pointer;padding:.85rem .9rem;text-align:left;background:transparent;border:0;">
                                    <div style="display:flex;align-items:center;gap:.65rem;min-width:220px;">
                                        <span style="width:2rem;height:2rem;border-radius:.7rem;background:rgba(37,99,235,.1);display:inline-flex;align-items:center;justify-content:center;color:#2563eb;">{{ $isDayOpen ? '▴' : '▾' }}</span>
                                        <div>
                                            <h3 class="text-gray-950 dark:text-white" style="font-size:.92rem;font-weight:850;margin:0;">{{ $day['title'] ?: 'Untitled day' }}</h3>
                                            <p class="text-gray-500 dark:text-gray-400" style="font-size:.68rem;margin:.1rem 0 0;">{{ $day['date'] ?: 'Date not set' }}</p>
                                        </div>
                                    </div>
                                    <div style="display:flex;gap:.35rem;flex-wrap:wrap;">
                                        <x-filament::badge color="gray">{{ $dayImages->count() }} images</x-filament::badge>
                                        <x-filament::badge color="gray">{{ $dayFiles->count() }} files</x-filament::badge>
                                        <x-filament::badge color="gray">{{ count($dayLinks) }} links</x-filament::badge>
                                    </div>
                                </button>

                                @if($isDayOpen)
                                <div style="padding:0 .9rem .9rem;">

                                @if($canManageDay)
                                    <div style="display:grid;gap:.65rem;margin-top:.85rem;">
                                        <div style="display:grid;grid-template-columns:minmax(0,1.3fr) 160px;gap:.55rem;">
                                            <input type="text" wire:focus="startProjectEditing('mobility', 'evidence-day:{{ $day['id'] }}', 'Evidence day: {{ addslashes($day['title'] ?: 'Untitled day') }}')" wire:model.live.debounce.700ms="evidenceDays.{{ $day['id'] }}.title" aria-label="Evidence day title" placeholder="Day title" style="width:100%;padding:.62rem .72rem;border:1px solid rgba(100,116,139,.28);border-radius:.65rem;background:transparent;">
                                            <input type="date" wire:focus="startProjectEditing('mobility', 'evidence-day:{{ $day['id'] }}', 'Evidence day: {{ addslashes($day['title'] ?: 'Untitled day') }}')" wire:model.live.debounce.700ms="evidenceDays.{{ $day['id'] }}.date" aria-label="Evidence day date" style="width:100%;padding:.62rem .72rem;border:1px solid rgba(100,116,139,.28);border-radius:.65rem;background:transparent;">
                                        </div>
                                        @error('evidenceDays.'.$day['id'].'.title') <span style="display:block;color:#dc2626;font-size:11px;">{{ $message }}</span> @enderror

                                        <textarea rows="3" wire:focus="startProjectEditing('mobility', 'evidence-day:{{ $day['id'] }}', 'Evidence day: {{ addslashes($day['title'] ?: 'Untitled day') }}')" wire:model.live.debounce.900ms="evidenceDays.{{ $day['id'] }}.description" placeholder="Describe the day: programme, activities, participants, outputs and important moments." aria-label="Evidence day description" style="width:100%;padding:.65rem .75rem;border:1px solid rgba(100,116,139,.25);border-radius:.7rem;background:transparent;font-size:.78rem;resize:vertical;"></textarea>
                                        <textarea rows="2" wire:focus="startProjectEditing('mobility', 'evidence-day:{{ $day['id'] }}', 'Evidence day: {{ addslashes($day['title'] ?: 'Untitled day') }}')" wire:model.live.debounce.900ms="evidenceDays.{{ $day['id'] }}.observations" placeholder="Observations, incidents, quality notes, changes from the plan or useful context for final reporting." aria-label="Evidence day observations" style="width:100%;padding:.65rem .75rem;border:1px solid rgba(100,116,139,.25);border-radius:.7rem;background:transparent;font-size:.78rem;resize:vertical;"></textarea>

                                        <div style="border:1px solid rgba(148,163,184,.18);border-radius:.75rem;padding:.7rem;display:grid;gap:.45rem;">
                                            <div style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;">
                                                <span class="text-gray-500 dark:text-gray-400" style="font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em;">Links</span>
                                                <x-filament::button wire:click="addEvidenceLink('{{ $day['id'] }}')" color="gray" size="xs" icon="heroicon-m-plus">Add link</x-filament::button>
                                            </div>
                                            @foreach($dayLinks as $linkIndex => $link)
                                                <div style="display:grid;grid-template-columns:130px minmax(0,1fr) auto;gap:.45rem;align-items:center;">
                                                    <input type="text" wire:focus="startProjectEditing('mobility', 'evidence-day:{{ $day['id'] }}', 'Evidence day: {{ addslashes($day['title'] ?: 'Untitled day') }}')" wire:model.live.debounce.700ms="evidenceDays.{{ $day['id'] }}.links.{{ $linkIndex }}.label" placeholder="Facebook" aria-label="Link label" style="width:100%;padding:.52rem .62rem;border:1px solid rgba(100,116,139,.25);border-radius:.6rem;background:transparent;font-size:.76rem;">
                                                    <input type="url" wire:focus="startProjectEditing('mobility', 'evidence-day:{{ $day['id'] }}', 'Evidence day: {{ addslashes($day['title'] ?: 'Untitled day') }}')" wire:model.live.debounce.700ms="evidenceDays.{{ $day['id'] }}.links.{{ $linkIndex }}.url" placeholder="https://..." aria-label="Link URL" style="width:100%;padding:.52rem .62rem;border:1px solid rgba(100,116,139,.25);border-radius:.6rem;background:transparent;font-size:.76rem;">
                                                    <x-filament::icon-button wire:click="removeEvidenceLink('{{ $day['id'] }}', '{{ $link['id'] }}')" icon="heroicon-m-x-mark" color="gray" label="Remove link" />
                                                </div>
                                            @endforeach
                                        </div>

                                        <div style="display:flex;gap:.45rem;align-items:center;flex-wrap:wrap;">
                                            <span class="text-gray-500 dark:text-gray-400" style="font-size:.68rem;">Changes save automatically.</span>
                                            <x-filament::button wire:click="prepareEvidenceImageUpload('{{ $day['id'] }}')" color="gray" size="sm" icon="heroicon-m-camera">
                                                Images
                                            </x-filament::button>
                                            <x-filament::button wire:click="prepareEvidenceFileUpload('{{ $day['id'] }}')" color="gray" size="sm" icon="heroicon-m-paper-clip">
                                                Files
                                            </x-filament::button>
                                            <x-filament::button wire:click="deleteEvidenceDay('{{ $day['id'] }}')" wire:confirm="Delete this evidence day? Uploaded files will remain in the project files list." color="danger" size="sm" icon="heroicon-m-trash">
                                                Delete day
                                            </x-filament::button>
                                        </div>

                                        @if($evidenceUploadDayId === $day['id'].'_images')
                                            <div style="padding:.75rem;border:1px dashed rgba(37,99,235,.35);border-radius:.75rem;background:rgba(37,99,235,.045);display:grid;gap:.55rem;">
                                                <strong style="font-size:.78rem;">Upload images for {{ $day['title'] }}</strong>
                                                <input type="text" wire:model="evidenceImageTitle" aria-label="Evidence image title" style="width:100%;padding:.58rem .68rem;border:1px solid rgba(100,116,139,.25);border-radius:.6rem;background:transparent;">
                                                <textarea rows="2" wire:model="evidenceUploadNotes" placeholder="Short note for this batch of images." style="width:100%;padding:.58rem .68rem;border:1px solid rgba(100,116,139,.25);border-radius:.6rem;background:transparent;resize:vertical;"></textarea>
                                                <input type="file" wire:model="evidenceImageUploads" multiple accept=".jpg,.jpeg,.png,.webp" aria-label="Upload evidence images" style="font-size:.76rem;">
                                                @error('evidenceImageUploads') <span style="display:block;color:#dc2626;font-size:11px;">{{ $message }}</span> @enderror
                                                @error('evidenceImageUploads.*') <span style="display:block;color:#dc2626;font-size:11px;">{{ $message }}</span> @enderror
                                                <x-filament::button wire:click="uploadEvidenceImages('{{ $day['id'] }}')" wire:loading.attr="disabled" wire:target="uploadEvidenceImages,evidenceImageUploads" size="sm" icon="heroicon-m-arrow-up-tray">
                                                    Upload selected images
                                                </x-filament::button>
                                            </div>
                                        @endif

                                        @if($evidenceUploadDayId === $day['id'].'_files')
                                            <div style="padding:.75rem;border:1px dashed rgba(15,118,110,.35);border-radius:.75rem;background:rgba(15,118,110,.045);display:grid;gap:.55rem;">
                                                <strong style="font-size:.78rem;">Upload files for {{ $day['title'] }}</strong>
                                                <input type="text" wire:model="evidenceFileTitle" aria-label="Evidence file title" style="width:100%;padding:.58rem .68rem;border:1px solid rgba(100,116,139,.25);border-radius:.6rem;background:transparent;">
                                                <textarea rows="2" wire:model="evidenceUploadNotes" placeholder="What are these files? Participant outputs, presentations, worksheets..." style="width:100%;padding:.58rem .68rem;border:1px solid rgba(100,116,139,.25);border-radius:.6rem;background:transparent;resize:vertical;"></textarea>
                                                <input type="file" wire:model="evidenceFileUploads" multiple accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip" aria-label="Upload evidence files" style="font-size:.76rem;">
                                                @error('evidenceFileUploads') <span style="display:block;color:#dc2626;font-size:11px;">{{ $message }}</span> @enderror
                                                @error('evidenceFileUploads.*') <span style="display:block;color:#dc2626;font-size:11px;">{{ $message }}</span> @enderror
                                                <x-filament::button wire:click="uploadEvidenceFiles('{{ $day['id'] }}')" wire:loading.attr="disabled" wire:target="uploadEvidenceFiles,evidenceFileUploads" size="sm" icon="heroicon-m-arrow-up-tray">
                                                    Upload selected files
                                                </x-filament::button>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <p class="text-gray-500 dark:text-gray-400" style="font-size:.8rem;line-height:1.55;margin:.75rem 0 0;">{{ $day['description'] }}</p>
                                    @if($day['observations'])
                                        <p class="text-gray-500 dark:text-gray-400" style="font-size:.74rem;line-height:1.5;margin:.45rem 0 0;"><strong>Observations:</strong> {{ $day['observations'] }}</p>
                                    @endif
                                @endif

                                @if(count($day['links'] ?? []) > 0)
                                    <div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-top:.75rem;">
                                        @foreach($day['links'] as $link)
                                            @if(filled($link['url'] ?? null))
                                                <x-filament::button tag="a" :href="$link['url']" target="_blank" color="gray" size="xs" icon="heroicon-m-arrow-top-right-on-square">
                                                    {{ $link['label'] ?: 'Link' }}
                                                </x-filament::button>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif

                                @if($dayImages->isNotEmpty() || $dayFiles->isNotEmpty())
                                    <div style="margin-top:.85rem;">
                                        <strong style="font-size:.75rem;">Files and images</strong>
                                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(145px,1fr));gap:.6rem;margin-top:.5rem;">
                                            @foreach($dayImages->merge($dayFiles)->take(16) as $file)
                                                @include('filament.pages.partials.project-file-card', [
                                                    'record' => $record,
                                                    'document' => $file,
                                                    'compact' => true,
                                                    'deleteMethod' => 'deleteMobilityDocument',
                                                    'deleteConfirm' => 'Delete this evidence file?',
                                                ])
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                                </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            </div>

            @php
                $photoFolderLock = $mobilityLocks->get('external-photo-folders');
                $photoFolderLockedByOther = $photoFolderLock && (int) $photoFolderLock->user_id !== (int) auth()->id();
                $photoFolderBadge = $photoFolderLock ? $this->projectLockBadge($photoFolderLock) : null;
                $canManagePhotoFolders = $canManage && ! $photoFolderLockedByOther;
                $finalVideoLock = $mobilityLocks->get('final-video');
                $finalVideoLockedByOther = $finalVideoLock && (int) $finalVideoLock->user_id !== (int) auth()->id();
                $finalVideoBadge = $finalVideoLock ? $this->projectLockBadge($finalVideoLock) : null;
                $canManageFinalVideo = $canManage && ! $finalVideoLockedByOther;
            @endphp
            <x-filament::section heading="External links" description="Useful when the photo/video set is too large for direct uploads." icon="heroicon-o-photo">
                <div style="display:grid;gap:.95rem;">
                    <div class="mc-lock-frame"
                         @if($photoFolderLock && (int) $photoFolderLock->user_id === (int) auth()->id()) wire:click.outside="stopProjectEditing('mobility', 'external-photo-folders')" @endif
                         style="{{ $this->projectLockFrameStyle($photoFolderLock, 'rgba(148,163,184,.18)', 'border-radius:.85rem;padding:.75rem;display:grid;gap:.55rem;') }}">
                        @if($photoFolderBadge)
                            @include('filament.pages.partials.project-lock-badge', [
                                'badge' => $photoFolderBadge,
                                'text' => $photoFolderLockedByOther ? $photoFolderBadge['name'].' edits photo folders' : 'You edit photo folders',
                            ])
                        @endif
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:.6rem;flex-wrap:wrap;">
                            <div>
                                <h3 class="text-gray-950 dark:text-white" style="font-size:.82rem;font-weight:800;margin:0;">Photo folders</h3>
                                <p class="text-gray-500 dark:text-gray-400" style="font-size:.68rem;margin:.12rem 0 0;">Google Drive, iCloud, Dropbox or another shared folder.</p>
                            </div>
                            <x-filament::badge :color="$summary['photo_folder_ready'] ? 'success' : 'gray'">
                                {{ $summary['photo_folder_links'] }} link{{ $summary['photo_folder_links'] === 1 ? '' : 's' }}
                            </x-filament::badge>
                        </div>

                        @if($photoFolderLinks === [])
                            <div style="border:1px dashed rgba(148,163,184,.28);border-radius:.75rem;padding:.75rem;text-align:center;">
                                <p class="text-gray-500 dark:text-gray-400" style="font-size:.72rem;margin:0;">No external photo folder added yet.</p>
                            </div>
                        @else
                            <div style="display:grid;gap:.45rem;">
                                @foreach($photoFolderLinks as $linkIndex => $link)
                                    <div style="display:grid;grid-template-columns:110px minmax(0,1fr) auto auto;gap:.4rem;align-items:center;">
                                        @if($canManagePhotoFolders)
                                            <input type="text" wire:focus="startProjectEditing('mobility', 'external-photo-folders', 'External photo folders')" wire:model.defer="photoFolderLinks.{{ $linkIndex }}.label" aria-label="External folder label" style="width:100%;padding:.5rem .58rem;border:1px solid rgba(100,116,139,.25);border-radius:.58rem;background:transparent;font-size:.74rem;">
                                            <input type="url" wire:focus="startProjectEditing('mobility', 'external-photo-folders', 'External photo folders')" wire:model.defer="photoFolderLinks.{{ $linkIndex }}.url" aria-label="External folder URL" style="width:100%;padding:.5rem .58rem;border:1px solid rgba(100,116,139,.25);border-radius:.58rem;background:transparent;font-size:.74rem;">
                                            <x-filament::icon-button tag="a" :href="$link['url']" target="_blank" icon="heroicon-m-arrow-top-right-on-square" color="gray" label="Open link" />
                                            <x-filament::icon-button wire:click="removePhotoFolderLink('{{ $link['id'] }}')" icon="heroicon-m-x-mark" color="gray" label="Remove link" />
                                        @else
                                            <span class="text-gray-500 dark:text-gray-400" style="font-size:.72rem;font-weight:750;">{{ $link['label'] ?: 'Photo folder' }}</span>
                                            <span class="text-gray-500 dark:text-gray-400" style="font-size:.68rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $link['url'] }}</span>
                                            <x-filament::icon-button tag="a" :href="$link['url']" target="_blank" icon="heroicon-m-arrow-top-right-on-square" color="gray" label="Open link" />
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if($canManagePhotoFolders)
                            <div style="display:grid;grid-template-columns:110px minmax(0,1fr) auto;gap:.4rem;align-items:start;">
                                <input type="text" wire:focus="startProjectEditing('mobility', 'external-photo-folders', 'External photo folders')" wire:model="newPhotoFolderLabel" placeholder="Drive" aria-label="New folder label" style="width:100%;padding:.52rem .62rem;border:1px solid rgba(100,116,139,.25);border-radius:.6rem;background:transparent;font-size:.76rem;">
                                <div>
                                    <input type="url" wire:focus="startProjectEditing('mobility', 'external-photo-folders', 'External photo folders')" wire:model="newPhotoFolderUrl" placeholder="https://drive.google.com/..." aria-label="New folder URL" style="width:100%;padding:.52rem .62rem;border:1px solid rgba(100,116,139,.25);border-radius:.6rem;background:transparent;font-size:.76rem;">
                                    @error('newPhotoFolderUrl') <span style="display:block;color:#dc2626;font-size:11px;margin-top:5px;">{{ $message }}</span> @enderror
                                </div>
                                <x-filament::button wire:click="addPhotoFolderLink" color="gray" size="sm" icon="heroicon-m-plus">
                                    Add
                                </x-filament::button>
                            </div>

                            @if($photoFolderLinks !== [])
                                <x-filament::button wire:click="savePhotoFolderLinks" color="gray" size="sm" icon="heroicon-m-check">
                                    Save folder links
                                </x-filament::button>
                            @endif
                        @endif
                    </div>

                    <div class="mc-lock-frame"
                         @if($finalVideoLock && (int) $finalVideoLock->user_id === (int) auth()->id()) wire:click.outside="stopProjectEditing('mobility', 'final-video')" @endif
                         style="{{ $this->projectLockFrameStyle($finalVideoLock, 'rgba(148,163,184,.18)', 'border-radius:.85rem;padding:.75rem;display:grid;gap:.55rem;') }}">
                        @if($finalVideoBadge)
                            @include('filament.pages.partials.project-lock-badge', [
                                'badge' => $finalVideoBadge,
                                'text' => $finalVideoLockedByOther ? $finalVideoBadge['name'].' edits final video' : 'You edit final video',
                            ])
                        @endif
                        <div>
                            <h3 class="text-gray-950 dark:text-white" style="font-size:.82rem;font-weight:800;margin:0;">Final mobility video</h3>
                            <p class="text-gray-500 dark:text-gray-400" style="font-size:.68rem;margin:.12rem 0 0;">Usually YouTube, Vimeo or another public/unlisted video link.</p>
                        </div>
                        <input type="url" wire:focus="startProjectEditing('mobility', 'final-video', 'Final mobility video')" wire:model.defer="finalMobilityVideoUrl" placeholder="https://youtube.com/..." aria-label="Final mobility video URL" @disabled(!$canManageFinalVideo) style="width:100%;padding:.58rem .68rem;border:1px solid rgba(100,116,139,.25);border-radius:.65rem;background:transparent;font-size:.76rem;">
                        @error('finalMobilityVideoUrl') <span style="display:block;color:#dc2626;font-size:11px;margin-top:5px;">{{ $message }}</span> @enderror
                        <div style="display:flex;gap:.45rem;align-items:center;flex-wrap:wrap;">
                            @if($canManageFinalVideo)
                                <x-filament::button wire:click="saveFinalMobilityVideo" color="gray" size="sm" icon="heroicon-m-video-camera">
                                    Save video link
                                </x-filament::button>
                            @endif
                            <x-filament::badge :color="$summary['final_video_ready'] ? 'success' : 'gray'">
                                {{ $summary['final_video_ready'] ? 'Video link saved' : 'No video link' }}
                            </x-filament::badge>
                            @if($summary['final_video_ready'])
                                <x-filament::button tag="a" :href="$finalMobilityVideoUrl" target="_blank" color="gray" size="sm" icon="heroicon-m-arrow-top-right-on-square">
                                    Open video
                                </x-filament::button>
                            @endif
                        </div>
                    </div>
                </div>
            </x-filament::section>
        </div>
    @endif
    @endif
</x-filament-panels::page>
