@php
    $workspace = \Filament\Facades\Filament::getTenant();
@endphp

@if ($workspace && ($workspace->plan === 'demo' || $workspace->subscription_status === 'demo'))
    <div style="margin: .75rem 1rem 0; border-radius: 1rem; border: 1px solid rgba(217, 119, 6, .28); background: rgba(251, 191, 36, .14); padding: .8rem 1rem; color: rgb(120, 53, 15);">
        <strong>Demo workspace</strong>
        <span>— this environment is intended for testing and presentations, not live client delivery.</span>
    </div>
@elseif ($workspace && \App\Support\WorkspaceAccess::hasOwnerGrantedAccess($workspace))
    <div style="margin: .75rem 1rem 0; border-radius: 1rem; border: 1px solid rgba(37, 99, 235, .28); background: rgba(59, 130, 246, .12); padding: .8rem 1rem; color: rgb(30, 64, 175);">
        <strong>Manual access active</strong>
        <span>— MobilityCloud owner access override is enabled{{ $workspace->access_override_ends_at ? ' until '.$workspace->access_override_ends_at->format('d M Y') : '' }}.</span>
    </div>
@elseif ($workspace && \App\Support\WorkspaceAccess::isReadOnly($workspace))
    <div style="margin: .75rem 1rem 0; border-radius: 1rem; border: 1px solid rgba(220, 38, 38, .28); background: rgba(248, 113, 113, .12); padding: .8rem 1rem; color: rgb(127, 29, 29);">
        <strong>Workspace read-only</strong>
        <span>— subscription access is expired or suspended. You can review data, but changes are restricted.</span>
    </div>
@elseif ($workspace && \App\Support\WorkspaceAccess::isInGracePeriod($workspace))
    <div style="margin: .75rem 1rem 0; border-radius: 1rem; border: 1px solid rgba(217, 119, 6, .28); background: rgba(251, 191, 36, .14); padding: .8rem 1rem; color: rgb(120, 53, 15);">
        <strong>Subscription grace period</strong>
        <span>— this workspace remains active for a short grace period after expiration.</span>
    </div>
@endif
