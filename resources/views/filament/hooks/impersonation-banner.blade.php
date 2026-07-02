@php
    $impersonator = session('impersonator_id') ? \App\Models\User::find(session('impersonator_id')) : null;
    $target = auth()->user();
@endphp

@if ($impersonator && $target)
    <div style="margin: .75rem 1rem 0; border-radius: 1rem; border: 1px solid rgba(217, 119, 6, .35); background: rgba(251, 191, 36, .14); padding: .8rem 1rem; color: rgb(120, 53, 15); display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
        <div style="font-size: .9rem;">
            <strong>Impersonation active</strong>
            <span>— {{ $impersonator->email }} is viewing MobilityCloud as {{ $target->email }}.</span>
        </div>

        <a
            href="{{ route('platform.impersonation.stop') }}"
            style="display: inline-flex; align-items: center; justify-content: center; border-radius: .75rem; background: rgb(120, 53, 15); color: white; padding: .55rem .85rem; font-size: .82rem; font-weight: 700; text-decoration: none;"
        >
            Exit impersonation
        </a>
    </div>
@endif
