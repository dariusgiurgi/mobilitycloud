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
    $documentLock = $documentLock ?? null;
    $documentLockedByOther = $documentLock && (int) $documentLock->user_id !== (int) auth()->id();
    $documentBadge = $documentLock ? $this->projectLockBadge($documentLock) : null;
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

@once
    <style>
        .mc-file-card {
            position: relative;
            min-width: 0;
        }

        .mc-file-card * {
            min-width: 0;
        }

        .mc-file-card-title,
        .mc-file-card-filename,
        .mc-file-card-meta {
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .mc-file-card-meta {
            white-space: nowrap;
        }

        .mc-file-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .45rem;
            min-width: 0;
        }

        .mc-file-card-status {
            min-width: 0;
            flex: 1 1 auto;
            overflow: hidden;
        }

        .mc-file-card-status .fi-badge {
            max-width: 100%;
        }

        .mc-file-card-status .fi-badge-label {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .mc-file-card-actions {
            position: relative;
            flex: 0 0 auto;
        }

        .mc-file-card-actions > summary {
            list-style: none;
            width: 2rem;
            height: 2rem;
            border-radius: .6rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            cursor: pointer;
        }

        .mc-file-card-actions > summary::-webkit-details-marker {
            display: none;
        }

        .mc-file-card-actions > summary:hover,
        .mc-file-card-actions[open] > summary {
            background: rgba(100, 116, 139, .1);
            color: #111827;
        }

        .dark .mc-file-card-actions > summary:hover,
        .dark .mc-file-card-actions[open] > summary {
            color: #f9fafb;
        }

        .mc-file-card-actions-menu {
            position: absolute;
            right: 0;
            bottom: calc(100% + .35rem);
            z-index: 50;
            width: min(10.75rem, calc(100vw - 2rem));
            max-width: 10.75rem;
            padding: .35rem;
            border: 1px solid rgba(148, 163, 184, .24);
            border-radius: .75rem;
            background: white;
            box-shadow: 0 18px 40px rgba(15, 23, 42, .18);
        }

        .dark .mc-file-card-actions-menu {
            background: #111827;
            border-color: rgba(255, 255, 255, .1);
        }

        .mc-file-card-actions-item {
            width: 100%;
            border: 0;
            border-radius: .55rem;
            background: transparent;
            color: #374151;
            display: flex;
            align-items: center;
            gap: .5rem;
            padding: .5rem .55rem;
            text-align: left;
            text-decoration: none;
            font-size: .75rem;
            font-weight: 650;
            line-height: 1.25;
        }

        .mc-file-card-actions-item:hover {
            background: rgba(99, 102, 241, .08);
            color: #312e81;
        }

        .dark .mc-file-card-actions-item {
            color: #e5e7eb;
        }

        .dark .mc-file-card-actions-item:hover {
            background: rgba(99, 102, 241, .16);
            color: #c7d2fe;
        }

        .mc-file-card-actions-item-danger {
            color: #dc2626;
        }

        .mc-file-card-actions-item-danger:hover {
            background: rgba(239, 68, 68, .08);
            color: #991b1b;
        }

        .mc-file-card-actions-icon {
            width: 1rem;
            height: 1rem;
            flex: 0 0 auto;
        }
    </style>
@endonce

<div class="mc-file-card mc-lock-frame bg-white dark:bg-gray-900"
     @if($documentLock && (int) $documentLock->user_id === (int) auth()->id()) wire:click.outside="stopProjectEditing('documents', 'document:{{ $document->id }}')" @endif
     style="{{ $this->projectLockFrameStyle($documentLock, 'rgba(148,163,184,.24)', 'border-radius:1rem;display:flex;flex-direction:column;min-height:'.($compact ? '190px' : '230px').';') }}">
    @if($documentBadge)
        @include('filament.pages.partials.project-lock-badge', [
            'badge' => $documentBadge,
            'text' => $documentLockedByOther ? $documentBadge['name'].' edits this file' : 'You edit this file',
        ])
    @endif
    <div style="position:relative;aspect-ratio:{{ $compact ? '16/10' : '4/3' }};background:linear-gradient(135deg,rgba(148,163,184,.12),rgba(148,163,184,.04));overflow:hidden;border-radius:1rem 1rem 0 0;">
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
            <div class="mc-file-card-title text-gray-950 dark:text-white" title="{{ $title }}" style="font-size:.82rem;font-weight:800;line-height:1.25;white-space:nowrap;">
                {{ $title }}
            </div>
            @if($fileName)
                <div class="mc-file-card-filename text-gray-500 dark:text-gray-400" title="{{ $fileName }}" style="font-size:.66rem;line-height:1.3;margin-top:.12rem;white-space:nowrap;">
                    {{ $fileName }}
                </div>
            @endif
            @if($meta->isNotEmpty())
                <div class="mc-file-card-meta text-gray-500 dark:text-gray-400" style="font-size:.61rem;line-height:1.35;margin-top:.18rem;">
                    {{ $meta->join(' · ') }}
                </div>
            @endif
            @if($document->notes)
                <div class="text-gray-500 dark:text-gray-400" style="font-size:.64rem;line-height:1.35;margin-top:.25rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                    {{ $document->notes }}
                </div>
            @endif
        </div>

        <div class="mc-file-card-footer" style="margin-top:auto;">
            <div class="mc-file-card-status">
            @if($isGenerated && $showSignedWorkflow)
                <x-filament::badge :color="$document->hasSignedCopy() ? 'success' : 'warning'" size="sm">{{ $document->statusLabel() }}</x-filament::badge>
            @elseif($showCategory)
                <x-filament::badge color="gray" size="sm">{{ $document->categoryLabel() }}</x-filament::badge>
            @else
                <span></span>
            @endif
            </div>

            <div style="display:flex;align-items:center;gap:.25rem;">
                @if($isGenerated && $showSignedWorkflow && $canManage && ! $document->hasSignedCopy())
                    <x-filament::icon-button wire:click="openSignedUpload({{ $document->id }})" icon="heroicon-m-arrow-up-tray" color="warning" size="sm" label="Upload signed copy" />
                @endif

                <details class="mc-file-card-actions">
                    <summary aria-label="File actions">
                        <x-filament::icon icon="heroicon-m-ellipsis-vertical" class="h-5 w-5" />
                    </summary>
                    <div class="mc-file-card-actions-menu">
                        @if($downloadUrl)
                            <a class="mc-file-card-actions-item" href="{{ $downloadUrl }}">
                                <x-filament::icon icon="heroicon-m-arrow-down-tray" class="mc-file-card-actions-icon" />
                                {{ $isGenerated ? 'Download generated PDF' : 'Download file' }}
                            </a>
                        @endif
                        @if($signedDownloadUrl)
                            <a class="mc-file-card-actions-item" href="{{ $signedDownloadUrl }}">
                                <x-filament::icon icon="heroicon-m-check-badge" class="mc-file-card-actions-icon" />
                                Download signed copy
                            </a>
                        @endif
                        @if($isGenerated && $showSignedWorkflow && $canManage)
                            <button type="button" class="mc-file-card-actions-item" wire:click="openSignedUpload({{ $document->id }})">
                                <x-filament::icon icon="heroicon-m-arrow-up-tray" class="mc-file-card-actions-icon" />
                                {{ $document->hasSignedCopy() ? 'Replace signed copy' : 'Upload signed copy' }}
                            </button>
                            @if($document->hasSignedCopy())
                                <button type="button" class="mc-file-card-actions-item mc-file-card-actions-item-danger" wire:click="deleteSignedCopy({{ $document->id }})" wire:confirm="Remove the signed copy?">
                                    <x-filament::icon icon="heroicon-m-trash" class="mc-file-card-actions-icon" />
                                    Remove signed copy
                                </button>
                            @endif
                        @endif
                        @if($canManage && $showDelete)
                            <button type="button" class="mc-file-card-actions-item mc-file-card-actions-item-danger" wire:click="{{ $deleteMethod }}({{ $document->id }})" wire:confirm="{{ $deleteConfirm }}">
                                <x-filament::icon icon="heroicon-m-trash" class="mc-file-card-actions-icon" />
                                Delete file
                            </button>
                        @endif
                    </div>
                </details>
            </div>
        </div>
    </div>
</div>
