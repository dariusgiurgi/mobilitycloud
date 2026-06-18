<div style="font-size:13px;line-height:1.6;">
    @if($block)
        <div style="display:flex;gap:.35rem;flex-wrap:wrap;margin-bottom:1rem;">
            <x-filament::badge size="sm" color="primary">{{ $block->categoryLabel() }}</x-filament::badge>
            <x-filament::badge size="sm" color="gray">{{ strtoupper($block->ka_action) }}</x-filament::badge>
            @if($block->is_proven)<x-filament::badge size="sm" color="success">Verified</x-filament::badge>@endif
            @if($block->is_hidden)<x-filament::badge size="sm" color="danger">Hidden</x-filament::badge>@endif
        </div>

        <div style="white-space:pre-wrap;">{{ $block->body }}</div>

        @if($block->source_note)
            <div style="margin-top:1rem;font-size:11px;color:#9ca3af;">Source: {{ $block->source_note }}</div>
        @endif
    @else
        <p style="color:#9ca3af;">This block has already been deleted.</p>
    @endif
</div>