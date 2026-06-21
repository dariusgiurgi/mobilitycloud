@props(['title', 'id' => null])

@once
    <style>
        [x-cloak] { display:none !important; }
        .mc-help-wrap { position:relative;display:inline-flex;vertical-align:middle; }
        .mc-help-trigger { width:18px;height:18px;display:inline-flex;align-items:center;justify-content:center;border:1px solid rgba(100,116,139,.35);border-radius:9999px;background:transparent;color:#64748b;font-size:11px;font-weight:700;line-height:1;cursor:pointer;transition:color .15s,border-color .15s,background .15s; }
        .mc-help-trigger:hover,.mc-help-trigger:focus-visible { color:#4f46e5;border-color:#6366f1;background:rgba(99,102,241,.08);outline:none; }
        .mc-help-popover { position:absolute;z-index:40;left:0;top:calc(100% + 8px);width:min(280px,calc(100vw - 2rem));padding:.8rem;border:1px solid rgba(148,163,184,.24);border-radius:.65rem;background:#fff;color:#475569;box-shadow:0 14px 34px rgba(15,23,42,.16);font-size:.76rem;font-weight:400;line-height:1.5;text-transform:none;letter-spacing:normal;overflow-wrap:anywhere; }
        .mc-help-popover strong { display:block;color:#0f172a;font-size:.8rem;margin-bottom:.25rem; }
        .dark .mc-help-trigger { color:#94a3b8;border-color:rgba(255,255,255,.2); }
        .dark .mc-help-popover { background:#18212f;color:#cbd5e1;border-color:rgba(255,255,255,.12);box-shadow:0 14px 34px rgba(0,0,0,.35); }
        .dark .mc-help-popover strong { color:#f8fafc; }
    </style>
@endonce

<span
    class="mc-help-wrap"
    x-data="{ open: false, offset: 0 }"
    x-on:click.outside="open = false"
    x-on:keydown.escape.window="open = false"
    x-on:resize.window="
        if (open) {
            const triggerLeft = $refs.trigger.getBoundingClientRect().left;
            const maximumLeft = window.innerWidth - $refs.panel.offsetWidth - 16;
            offset = Math.max(16, Math.min(triggerLeft, maximumLeft)) - triggerLeft;
        }
    "
>
    <button
        type="button"
        x-ref="trigger"
        class="mc-help-trigger"
        aria-label="More information about {{ $title }}"
        aria-controls="{{ $id ? 'help-'.$id : null }}"
        x-bind:aria-expanded="open.toString()"
        x-on:click="
            open = ! open;
            if (open) {
                $nextTick(() => {
                    const triggerLeft = $refs.trigger.getBoundingClientRect().left;
                    const maximumLeft = window.innerWidth - $refs.panel.offsetWidth - 16;
                    offset = Math.max(16, Math.min(triggerLeft, maximumLeft)) - triggerLeft;
                });
            }
        "
    >?</button>
    <span
        x-cloak
        x-ref="panel"
        x-show="open"
        x-bind:style="`left:${offset}px;right:auto`"
        x-transition.opacity.duration.150ms
        @if ($id) id="help-{{ $id }}" @endif
        class="mc-help-popover"
        role="tooltip"
    >
        <strong>{{ $title }}</strong>
        {{ $slot }}
    </span>
</span>
