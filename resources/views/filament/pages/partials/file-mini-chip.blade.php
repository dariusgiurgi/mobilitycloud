@php
    $extension = strtolower((string) pathinfo((string) $name, PATHINFO_EXTENSION));
    $kind = match ($extension) {
        'jpg', 'jpeg', 'png', 'webp', 'gif' => 'image',
        'pdf' => 'pdf',
        'doc', 'docx', 'odt', 'rtf' => 'word',
        'xls', 'xlsx', 'csv', 'ods' => 'excel',
        'ppt', 'pptx', 'odp' => 'powerpoint',
        'zip', 'rar', '7z' => 'archive',
        default => 'file',
    };
    $label = match ($kind) {
        'image' => 'IMG',
        'pdf' => 'PDF',
        'word' => 'W',
        'excel' => 'E',
        'powerpoint' => 'P',
        'archive' => 'ZIP',
        default => strtoupper($extension ?: 'FILE'),
    };
    $accent = match ($kind) {
        'image', 'word' => '#2563eb',
        'pdf' => '#dc2626',
        'excel' => '#16a34a',
        'powerpoint' => '#ea580c',
        'archive' => '#7c3aed',
        default => '#64748b',
    };
@endphp

<span style="display:inline-flex;align-items:center;gap:.42rem;min-width:0;max-width:100%;">
    <a href="{{ $url }}"
       target="{{ $target ?? '_blank' }}"
       title="{{ $name }}"
       style="display:inline-flex;align-items:center;gap:.42rem;min-width:0;max-width:100%;padding:.25rem .42rem;border:1px solid rgba(148,163,184,.25);border-radius:.55rem;background:rgba(148,163,184,.06);text-decoration:none;">
        <span style="min-width:1.85rem;height:1.45rem;padding:0 .24rem;border-radius:.35rem;background:{{ $accent }};color:white;font-size:.55rem;font-weight:900;letter-spacing:.03em;display:inline-flex;align-items:center;justify-content:center;">
            {{ $label }}
        </span>
        <span class="text-gray-700 dark:text-gray-200" style="font-size:.68rem;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
            {{ $name }}
        </span>
        @if(filled($size ?? null))
            <span class="text-gray-400" style="font-size:.6rem;white-space:nowrap;">{{ $size }}</span>
        @endif
    </a>

    @if(filled($deleteAction ?? null))
        <button type="button"
                wire:click="{{ $deleteAction }}"
                title="{{ $deleteTitle ?? 'Remove file' }}"
                style="border:none;background:transparent;cursor:pointer;color:#9ca3af;font-size:.78rem;line-height:1;"
                onmouseover="this.style.color='#dc2626';"
                onmouseout="this.style.color='#9ca3af';">×</button>
    @endif
</span>
