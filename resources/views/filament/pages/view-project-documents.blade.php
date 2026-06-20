<x-filament-panels::page>
    @php $documents = $this->getDocuments(); @endphp

    <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;flex-wrap:wrap;">
        <p class="text-gray-500 dark:text-gray-400" style="font-size:13px;margin:0;">Generated documents and signed copies for this project.</p>
        <div style="flex:1;"></div>
        @if($record->canBeManagedBy(auth()->user()))
            <button type="button" wire:click="openAttendanceGenerator"
                    style="padding:8px 15px;border-radius:8px;border:none;background:#4f46e5;color:#fff;cursor:pointer;font-size:13px;font-weight:600;">
                Generate attendance list
            </button>
        @endif
    </div>

    @if($documents->isEmpty())
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
             style="padding:2.5rem;text-align:center;">
            <div style="font-size:34px;margin-bottom:.6rem;">📄</div>
            <h3 class="text-gray-950 dark:text-white" style="font-size:16px;font-weight:700;margin:0 0 .35rem;">No project documents yet</h3>
            <p class="text-gray-500 dark:text-gray-400" style="font-size:13px;margin:0;">Generate the first attendance list from here or from Participants.</p>
        </div>
    @else
        <div style="display:flex;flex-direction:column;gap:.75rem;">
            @foreach($documents as $document)
                <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
                     style="padding:1rem 1.1rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
                    <div style="font-size:24px;">📋</div>
                    <div style="flex:1;min-width:220px;">
                        <div class="text-gray-950 dark:text-white" style="font-size:14px;font-weight:700;">{{ $document->title }}</div>
                        <div class="text-gray-500 dark:text-gray-400" style="font-size:11px;margin-top:3px;">
                            {{ $document->activity_date?->format('d M Y') }}
                            @if($document->location) · {{ $document->location }} @endif
                            · Generated {{ $document->generated_at?->format('d M Y, H:i') }}
                        </div>
                    </div>

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
                        <button type="button" wire:click="deleteDocument({{ $document->id }})" wire:confirm="Delete this document record and its signed copy?"
                                style="border:none;background:transparent;color:#9ca3af;cursor:pointer;font-size:14px;">✕</button>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @include('filament.partials.attendance-generator-modal')

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
