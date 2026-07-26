@php
    $mode = $mode ?? 'floating';
    $text = $text ?? null;
    $badge = $badge ?? null;
@endphp

@if($badge)
    <span
        class="mc-lock-badge {{ $mode === 'inline' ? 'mc-lock-badge-inline' : 'mc-lock-badge-floating' }}"
        style="--mc-lock-color: {{ $badge['color'] }}; --mc-lock-shadow: {{ $badge['shadow'] ?? $badge['color'].'2b' }};"
    >
        {{ $text ?: $badge['name'].' edits' }}
    </span>
@endif
