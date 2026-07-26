@php
    $state = $this->projectPresenceState($module);
    $presences = $state['presences'];
@endphp

<div wire:poll.5s="refreshProjectCollaboration('{{ $module }}')" style="display:contents;">
    @if($presences->isNotEmpty())
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
             style="padding:.65rem .85rem;margin:.75rem 0 1rem;border:1px solid rgba(99,102,241,.18);">
            <div style="display:flex;align-items:center;gap:.55rem;flex-wrap:wrap;">
                <span class="text-gray-500 dark:text-gray-400" style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em;">
                    Also here
                </span>
                @foreach($presences as $presence)
                    @php
                        $color = $this->projectUserColor($presence->user);
                    @endphp
                    <span style="display:inline-flex;align-items:center;gap:.35rem;padding:.24rem .5rem;border-radius:999px;border:1px solid {{ $color }};background:{{ $color }}10;color:{{ $color }};font-size:.68rem;font-weight:750;">
                        <span style="width:.45rem;height:.45rem;border-radius:999px;background:{{ $color }};"></span>
                        {{ $presence->user?->name ?: 'User' }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif
</div>
