@php
    $user = auth()->user();
    $isImpersonating = session()->has('impersonator_id');
    $label = $user?->isPlatformOwner()
        ? 'Platform owner'
        : ($user?->isPlatformAdmin() ? 'Platform admin' : null);
@endphp

@if ($label)
    <div style="padding:.65rem 1rem 0;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:.8rem;flex-wrap:wrap;border:1px solid rgba(99,102,241,.16);background:linear-gradient(135deg,rgba(99,102,241,.10),rgba(14,165,233,.07));border-radius:.9rem;padding:.55rem .75rem;">
            <div style="display:flex;align-items:center;gap:.5rem;min-width:0;">
                <span style="display:inline-flex;width:.55rem;height:.55rem;border-radius:999px;background:#4f46e5;box-shadow:0 0 0 4px rgba(99,102,241,.13);"></span>
                <span style="font-size:.72rem;font-weight:850;color:#3730a3;letter-spacing:.01em;">Platform admin area</span>
                <span style="font-size:.68rem;color:#64748b;">Internal controls, billing access and audit operations</span>
            </div>

            <div style="display:flex;align-items:center;gap:.45rem;flex-wrap:wrap;">
                <span style="display:inline-flex;align-items:center;border-radius:999px;padding:.25rem .65rem;font-size:.68rem;font-weight:750;background:rgba(255,255,255,.55);color:#4f46e5;border:1px solid rgba(99,102,241,.18);">
                    {{ $label }}
                </span>

                @if ($isImpersonating)
                    <span style="display:inline-flex;align-items:center;border-radius:999px;padding:.25rem .65rem;font-size:.68rem;font-weight:750;background:rgba(245,158,11,.13);color:#b45309;border:1px solid rgba(245,158,11,.2);">
                        Impersonating
                    </span>
                @endif
            </div>
        </div>
    </div>
@endif
