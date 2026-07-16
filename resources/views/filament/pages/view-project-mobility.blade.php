<x-filament-panels::page>
    <x-ui-polish />
    @if (! $record->implementationModulesAvailable())
        @include('filament.pages.partials.project-module-locked', [
            'record' => $record,
            'module' => 'Mobility',
            'icon' => 'heroicon-o-map',
            'accent' => '#0f766e',
            'features' => [
                ['title' => 'Mobility report', 'body' => 'Write an implementation note about what happened during the mobility and what changed.'],
                ['title' => 'Activity evidence', 'body' => 'Upload plans, worksheets, participant outputs, photos and supporting files.'],
                ['title' => 'Final archive', 'body' => 'Mobility files are included in the final project archive in an ordered structure.'],
            ],
        ])
    @else
    @php
        $summary = $this->getMobilitySummary();
        $documents = $this->getMobilityDocuments();
        $categories = $this->getMobilityCategories();
    @endphp

    <x-filament::section>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
            <div style="min-width:240px;flex:1;">
                <h2 class="text-gray-950 dark:text-white" style="font-size:1rem;font-weight:750;margin:0;">Mobility workspace</h2>
                <p class="text-gray-500 dark:text-gray-400" style="font-size:.8rem;margin:.18rem 0 0;line-height:1.45;">Upload materials created during the mobility: plans, worksheets, participant outputs, photos, evidence and other activity files.</p>
            </div>
            <x-filament::button tag="a" :href="route('projects.final-archive', $record)" color="gray" icon="heroicon-o-archive-box-arrow-down">
                Download final archive
            </x-filament::button>
        </div>
    </x-filament::section>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:.65rem;margin-top:.8rem;">
        @foreach([
            ['label' => 'Mobility files', 'value' => $summary['files'], 'color' => '#4f46e5'],
            ['label' => 'Plans', 'value' => $summary['plans'], 'color' => '#0f766e'],
            ['label' => 'Materials', 'value' => $summary['materials'], 'color' => '#7c3aed'],
            ['label' => 'Outputs', 'value' => $summary['outputs'], 'color' => '#059669'],
        ] as $stat)
            <div class="bg-white dark:bg-gray-900" style="padding:.8rem .9rem;border:1px solid rgba(148,163,184,.22);border-radius:.85rem;">
                <p class="text-gray-400" style="font-size:.58rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;">{{ $stat['label'] }}</p>
                <p style="font-size:1.25rem;font-weight:850;margin-top:.2rem;color:{{ $stat['color'] }};">{{ $stat['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div style="display:grid;grid-template-columns:minmax(0,1fr) minmax(320px,.72fr);gap:1rem;margin-top:1rem;align-items:start;">
        <x-filament::section heading="Mobility implementation report" description="Use this for a short internal report about what happened during the mobility." icon="heroicon-o-clipboard-document-check">
            <textarea rows="8" wire:model.defer="mobilityReport"
                      aria-label="Mobility implementation report"
                      placeholder="Describe the mobility implementation: what was delivered, materials created, participant outputs, unexpected changes, learning moments and evidence kept in the archive."
                      style="width:100%;padding:.75rem .85rem;border:1px solid rgba(100,116,139,.28);border-radius:.75rem;background:transparent;font-size:.82rem;resize:vertical;"></textarea>
            @error('mobilityReport') <span style="display:block;color:#dc2626;font-size:11px;margin-top:5px;">{{ $message }}</span> @enderror

            @if($record->canBeManagedBy(auth()->user()))
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

        @if($record->canBeManagedBy(auth()->user()))
            <x-filament::section heading="Upload mobility document" description="Files uploaded here are included in the final project archive." icon="heroicon-o-arrow-up-tray">
                <div style="display:grid;gap:.65rem;">
                    <div>
                        <label class="text-gray-500 dark:text-gray-400" style="display:block;font-size:.62rem;font-weight:750;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.25rem;">Title *</label>
                        <input type="text" wire:model="documentTitle" aria-label="Mobility document title" style="width:100%;padding:.62rem .72rem;border:1px solid rgba(100,116,139,.28);border-radius:.65rem;background:transparent;">
                        @error('documentTitle') <span style="display:block;color:#dc2626;font-size:11px;margin-top:5px;">{{ $message }}</span> @enderror
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.55rem;">
                        <div>
                            <label class="text-gray-500 dark:text-gray-400" style="display:block;font-size:.62rem;font-weight:750;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.25rem;">Category *</label>
                            <select wire:model="documentCategory" aria-label="Mobility document category" style="width:100%;padding:.62rem .72rem;border:1px solid rgba(100,116,139,.28);border-radius:.65rem;background:transparent;">
                                @foreach($categories as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-gray-500 dark:text-gray-400" style="display:block;font-size:.62rem;font-weight:750;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.25rem;">Date</label>
                            <input type="date" wire:model="documentDate" style="width:100%;padding:.62rem .72rem;border:1px solid rgba(100,116,139,.28);border-radius:.65rem;background:transparent;">
                        </div>
                    </div>

                    <div>
                        <label class="text-gray-500 dark:text-gray-400" style="display:block;font-size:.62rem;font-weight:750;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.25rem;">Notes</label>
                        <textarea rows="3" wire:model="documentNotes" aria-label="Mobility document notes" style="width:100%;padding:.62rem .72rem;border:1px solid rgba(100,116,139,.28);border-radius:.65rem;background:transparent;resize:vertical;"></textarea>
                    </div>

                    <div>
                        <label class="text-gray-500 dark:text-gray-400" style="display:block;font-size:.62rem;font-weight:750;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.25rem;">File *</label>
                        <input type="file" wire:model="documentUpload" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip" aria-label="Mobility document file" style="width:100%;font-size:.78rem;">
                        @error('documentUpload') <span style="display:block;color:#dc2626;font-size:11px;margin-top:5px;">{{ $message }}</span> @enderror
                    </div>

                    <x-filament::button wire:click="uploadMobilityDocument" wire:loading.attr="disabled" wire:target="uploadMobilityDocument,documentUpload" icon="heroicon-m-arrow-up-tray">
                        <span wire:loading.remove wire:target="uploadMobilityDocument,documentUpload">Upload document</span>
                        <span wire:loading wire:target="uploadMobilityDocument,documentUpload">Uploading...</span>
                    </x-filament::button>
                </div>
            </x-filament::section>
        @endif
    </div>

    <x-filament::section heading="Mobility files" description="All files uploaded from this page are ordered by date, category and title." style="margin-top:1rem;">
        <div style="display:flex;align-items:center;justify-content:flex-end;gap:.65rem;flex-wrap:wrap;margin-bottom:.85rem;">
            <div style="width:min(280px,100%);">
                <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
                    <x-filament::input type="search" wire:model.live.debounce.300ms="documentSearch" placeholder="Search mobility files" />
                </x-filament::input.wrapper>
            </div>
            <div style="width:210px;">
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="categoryFilter">
                        <option value="">All categories</option>
                        @foreach($categories as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
        </div>

        @forelse($documents as $document)
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="padding:.9rem 1rem;display:flex;align-items:center;gap:.85rem;flex-wrap:wrap;margin-top:.55rem;">
                <div style="width:36px;height:36px;border-radius:.7rem;background:rgba(99,102,241,.1);display:flex;align-items:center;justify-content:center;flex:none;">
                    <x-filament::icon icon="heroicon-m-document" class="h-5 w-5 text-primary-600" />
                </div>
                <div style="flex:1;min-width:220px;">
                    <div class="text-gray-950 dark:text-white" style="font-size:.86rem;font-weight:750;">{{ $document->title }}</div>
                    <div class="text-gray-500 dark:text-gray-400" style="font-size:.68rem;margin-top:.14rem;">
                        {{ $document->categoryLabel() }}
                        @if($document->document_date) · {{ $document->document_date->format('d M Y') }} @endif
                        @if($document->file_name) · {{ $document->file_name }} ({{ $document->humanFileSize() }}) @endif
                    </div>
                    @if($document->notes)
                        <div class="text-gray-500 dark:text-gray-400" style="font-size:.68rem;margin-top:.25rem;line-height:1.4;">{{ $document->notes }}</div>
                    @endif
                </div>
                <x-filament::badge color="gray">{{ $document->categoryLabel() }}</x-filament::badge>
                <x-filament::button tag="a" :href="route('project-documents.file', [$record, $document])" color="gray" size="sm" icon="heroicon-m-arrow-down-tray">
                    Download
                </x-filament::button>
                @if($record->canBeManagedBy(auth()->user()))
                    <x-filament::icon-button wire:click="deleteMobilityDocument({{ $document->id }})" wire:confirm="Delete this mobility file?" icon="heroicon-m-trash" color="danger" label="Delete mobility file" />
                @endif
            </div>
        @empty
            <div class="mc-empty-state fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="padding:2rem;text-align:center;">
                <x-filament::icon icon="heroicon-o-folder-open" class="mx-auto h-10 w-10 text-gray-400" />
                <h3 class="text-gray-950 dark:text-white" style="font-size:1rem;font-weight:750;margin:.5rem 0 .25rem;">Collect the mobility evidence here</h3>
                <p class="text-gray-500 dark:text-gray-400" style="font-size:.8rem;line-height:1.55;margin:0 auto;max-width:34rem;">Upload agendas, activity plans, worksheets, photos, participant outputs, certificates and any files that should be included in the final project archive.</p>
            </div>
        @endforelse
    </x-filament::section>
    @endif
</x-filament-panels::page>
