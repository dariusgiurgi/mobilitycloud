@php
    use App\Models\ProjectDocument;

    /** @var \App\Models\Project $record */
    /** @var \App\Models\ProjectDocument $document */
    $canManage = $canManage ?? $record->canBeManagedBy(auth()->user());
    $showCategory = $showCategory ?? true;
    $showSignedWorkflow = $showSignedWorkflow ?? true;
    $deleteMethod = $deleteMethod ?? 'deleteDocument';
    $deleteConfirm = $deleteConfirm ?? 'Delete this document and its stored files?';
    $showDelete = $showDelete ?? true;
    $compact = $compact ?? false;
    $isGenerated = in_array($document->type, [ProjectDocument::TYPE_ATTENDANCE, ProjectDocument::TYPE_EXPENSE_REPORT], true);
    $downloadUrl = $downloadUrl ?? (
        $isGenerated
            ? route($document->type === ProjectDocument::TYPE_ATTENDANCE ? 'project-documents.attendance' : 'project-documents.expense-report', [$record, $document])
            : ($document->hasFile() ? route('project-documents.file', [$record, $document]) : null)
    );
    $signedDownloadUrl = $signedDownloadUrl ?? ($isGenerated && $document->hasSignedCopy() ? route('project-documents.signed', [$record, $document]) : null);
    $previewUrl = $document->hasFile() && $document->isImageFile()
        ? route('project-documents.file', [$record, $document, 'preview' => 1])
        : null;
    $accent = $document->fileAccent();
    $title = $document->title ?: ($document->file_name ?: 'Untitled file');
    $fileName = $document->file_name ?: ($isGenerated ? 'Generated PDF' : null);
    $dateLabel = $document->document_date?->format('d M Y')
        ?? $document->activity_date?->format('d M Y')
        ?? $document->created_at?->format('d M Y');
    $meta = collect();

    if ($showCategory) {
        $meta->push($document->categoryLabel());
    }

    if ($document->category === 'dissemination_evidence' && data_get($document->metadata, 'organisation_name')) {
        $meta->push(data_get($document->metadata, 'organisation_name'));
    }

    if ($dateLabel) {
        $meta->push($dateLabel);
    }

    if ($document->file_name && $document->file_size) {
        $meta->push($document->humanFileSize());
    }
@endphp

<div class="mc-file-card bg-white dark:bg-gray-900"
     style="border:1px solid rgba(148,163,184,.24);border-radius:1rem;overflow:hidden;box-shadow:0 12px 30px rgba(15,23,42,.06);display:flex;flex-direction:column;min-height:{{ $compact ? '190px' : '230px' }};">
    <div style="position:relative;aspect-ratio:{{ $compact ? '16/10' : '4/3' }};background:linear-gradient(135deg,rgba(148,163,184,.12),rgba(148,163,184,.04));overflow:hidden;">
        @if($previewUrl)
            <img src="{{ $previewUrl }}"
                 alt="{{ $fileName ?: $title }}"
                 loading="lazy"
                 style="width:100%;height:100%;object-fit:cover;display:block;">
            <div style="position:absolute;inset:auto 0 0 0;height:44%;background:linear-gradient(180deg,rgba(15,23,42,0),rgba(15,23,42,.55));"></div>
        @else
            <div style="height:100%;display:flex;align-items:center;justify-content:center;padding:1rem;">
                <div style="width:76px;height:92px;border-radius:.8rem;background:linear-gradient(180deg,rgba(255,255,255,.96),rgba(248,250,252,.92));border:1px solid rgba(148,163,184,.45);box-shadow:0 14px 28px rgba(15,23,42,.12);position:relative;display:flex;align-items:center;justify-content:center;">
                    <div style="position:absolute;right:-1px;top:-1px;width:22px;height:22px;background:rgba(148,163,184,.18);clip-path:polygon(0 0,100% 0,100% 100%);border-top-right-radius:.8rem;"></div>
                    <span style="color:{{ $accent }};font-weight:900;font-size:{{ strlen($document->fileBadgeLabel()) > 3 ? '.8rem' : '1.3rem' }};letter-spacing:.04em;">
                        {{ $document->fileBadgeLabel() }}
                    </span>
                </div>
            </div>
        @endif

        <span style="position:absolute;left:.55rem;top:.55rem;border-radius:999px;padding:.18rem .46rem;background:{{ $accent }};color:white;font-size:.58rem;font-weight:850;letter-spacing:.04em;box-shadow:0 8px 16px rgba(15,23,42,.15);">
            {{ $document->fileBadgeLabel() }}
        </span>

        @if($downloadUrl)
            <a href="{{ $downloadUrl }}"
               title="Download {{ $fileName ?: $title }}"
               style="position:absolute;right:.55rem;top:.55rem;width:2rem;height:2rem;border-radius:.65rem;background:rgba(15,23,42,.74);color:white;display:flex;align-items:center;justify-content:center;text-decoration:none;box-shadow:0 10px 22px rgba(15,23,42,.2);">
                <x-filament::icon icon="heroicon-m-arrow-down-tray" class="h-4 w-4" />
            </a>
        @endif
    </div>

    <div style="padding:.72rem .78rem .78rem;display:flex;flex-direction:column;gap:.5rem;flex:1;">
        <div style="min-width:0;">
            <div class="text-gray-950 dark:text-white" title="{{ $title }}" style="font-size:.82rem;font-weight:800;line-height:1.25;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                {{ $title }}
            </div>
            @if($fileName)
                <div class="text-gray-500 dark:text-gray-400" title="{{ $fileName }}" style="font-size:.66rem;line-height:1.3;margin-top:.12rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    {{ $fileName }}
                </div>
            @endif
            @if($meta->isNotEmpty())
                <div class="text-gray-500 dark:text-gray-400" style="font-size:.61rem;line-height:1.35;margin-top:.18rem;">
                    {{ $meta->join(' · ') }}
                </div>
            @endif
            @if($document->notes)
                <div class="text-gray-500 dark:text-gray-400" style="font-size:.64rem;line-height:1.35;margin-top:.25rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                    {{ $document->notes }}
                </div>
            @endif
        </div>

        <div style="margin-top:auto;display:flex;align-items:center;justify-content:space-between;gap:.45rem;">
            @if($isGenerated && $showSignedWorkflow)
                <x-filament::badge :color="$document->hasSignedCopy() ? 'success' : 'warning'" size="sm">{{ $document->statusLabel() }}</x-filament::badge>
            @elseif($showCategory)
                <x-filament::badge color="gray" size="sm">{{ $document->categoryLabel() }}</x-filament::badge>
            @else
                <span></span>
            @endif

            <div style="display:flex;align-items:center;gap:.25rem;">
                @if($isGenerated && $showSignedWorkflow && $canManage && ! $document->hasSignedCopy())
                    <x-filament::icon-button wire:click="openSignedUpload({{ $document->id }})" icon="heroicon-m-arrow-up-tray" color="warning" size="sm" label="Upload signed copy" />
                @endif

                <x-filament::dropdown placement="bottom-end" width="xs">
                    <x-slot name="trigger">
                        <x-filament::icon-button icon="heroicon-m-ellipsis-vertical" color="gray" size="sm" label="File actions" />
                    </x-slot>
                    <x-filament::dropdown.list>
                        @if($downloadUrl)
                            <x-filament::dropdown.list.item tag="a" :href="$downloadUrl" icon="heroicon-m-arrow-down-tray">
                                {{ $isGenerated ? 'Download generated PDF' : 'Download file' }}
                            </x-filament::dropdown.list.item>
                        @endif
                        @if($signedDownloadUrl)
                            <x-filament::dropdown.list.item tag="a" :href="$signedDownloadUrl" icon="heroicon-m-check-badge">
                                Download signed copy
                            </x-filament::dropdown.list.item>
                        @endif
                        @if($isGenerated && $showSignedWorkflow && $canManage)
                            <x-filament::dropdown.list.item wire:click="openSignedUpload({{ $document->id }})" icon="heroicon-m-arrow-up-tray">
                                {{ $document->hasSignedCopy() ? 'Replace signed copy' : 'Upload signed copy' }}
                            </x-filament::dropdown.list.item>
                            @if($document->hasSignedCopy())
                                <x-filament::dropdown.list.item wire:click="deleteSignedCopy({{ $document->id }})" wire:confirm="Remove the signed copy?" color="danger" icon="heroicon-m-trash">
                                    Remove signed copy
                                </x-filament::dropdown.list.item>
                            @endif
                        @endif
                        @if($canManage && $showDelete)
                            <x-filament::dropdown.list.item wire:click="{{ $deleteMethod }}({{ $document->id }})" wire:confirm="{{ $deleteConfirm }}" color="danger" icon="heroicon-m-trash">
                                Delete file
                            </x-filament::dropdown.list.item>
                        @endif
                    </x-filament::dropdown.list>
                </x-filament::dropdown>
            </div>
        </div>
    </div>
</div>
