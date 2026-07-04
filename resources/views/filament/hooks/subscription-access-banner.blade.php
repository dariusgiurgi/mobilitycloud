@php
    $workspace = \Filament\Facades\Filament::getTenant();
@endphp

@if ($workspace && ($workspace->plan === 'demo' || $workspace->subscription_status === 'demo'))
    <div class="mc-subscription-banner mc-subscription-banner-warning">
        <strong>Demo workspace</strong>
        <span>— this environment is intended for testing and presentations, not live client delivery.</span>
    </div>
@elseif ($workspace && \App\Support\WorkspaceAccess::hasOwnerGrantedAccess($workspace))
    <div class="mc-subscription-banner mc-subscription-banner-info">
        <strong>Manual access active</strong>
        <span>— MobilityCloud owner access override is enabled{{ $workspace->access_override_ends_at ? ' until '.$workspace->access_override_ends_at->format('d M Y') : '' }}.</span>
    </div>
@elseif ($workspace && \App\Support\WorkspaceAccess::isReadOnly($workspace))
    <div class="mc-subscription-banner mc-subscription-banner-danger">
        <strong>Workspace read-only</strong>
        <span>— subscription access is expired or suspended. You can review data, but changes are restricted.</span>
    </div>
@elseif ($workspace && \App\Support\WorkspaceAccess::isInGracePeriod($workspace))
    <div class="mc-subscription-banner mc-subscription-banner-warning">
        <strong>Subscription grace period</strong>
        <span>— this workspace remains active for a short grace period after expiration.</span>
    </div>
@endif

@once
    <style>
        .mc-subscription-banner {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .2rem .35rem;
            max-width: calc(100% - 2rem);
            margin: .75rem 1rem 0;
            border-radius: 1rem;
            padding: .8rem 1rem;
            overflow-wrap: anywhere;
            line-height: 1.35;
        }

        .mc-subscription-banner-warning {
            border: 1px solid rgba(217, 119, 6, .28);
            background: rgba(251, 191, 36, .14);
            color: rgb(120, 53, 15);
        }

        .mc-subscription-banner-info {
            border: 1px solid rgba(37, 99, 235, .28);
            background: rgba(59, 130, 246, .12);
            color: rgb(30, 64, 175);
        }

        .mc-subscription-banner-danger {
            border: 1px solid rgba(220, 38, 38, .28);
            background: rgba(248, 113, 113, .12);
            color: rgb(127, 29, 29);
        }

        .fi-main-sidebar .fi-dropdown-panel,
        .fi-main-sidebar .fi-dropdown-list {
            min-width: 15rem;
            max-width: min(22rem, calc(100vw - 2rem));
        }

        .fi-main-sidebar .fi-dropdown-list-item-label {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
@endonce
