@php
    $user = auth()->user();
    $isImpersonating = session()->has('impersonator_id');
    $label = $user?->isPlatformOwner()
        ? 'Platform owner'
        : ($user?->isPlatformAdmin() ? 'Platform admin' : null);
@endphp

@if ($label)
    <div class="mc-admin-context">
        <span><x-heroicon-o-squares-2x2 /><strong>Administration</strong><span class="mc-admin-context-detail">Accounts, billing & operations</span></span>
        <span>
            @if ($isImpersonating)
                <span class="mc-admin-context-warning">Impersonating</span>
            @endif
            <span class="mc-admin-context-role">{{ $label }}</span>
        </span>
    </div>
@endif
