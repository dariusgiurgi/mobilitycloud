<div style="display:grid;gap:1rem;">
    <div>
        <p style="font-size:.78rem;color:#64748b;margin:0;">Project</p>
        <p style="font-weight:700;margin:.15rem 0 0;">{{ $project->name }}</p>
    </div>

    <div style="display:grid;gap:.65rem;">
        @forelse($billing as $label => $value)
            <div style="display:grid;grid-template-columns:150px minmax(0,1fr);gap:.75rem;align-items:start;">
                <span style="font-size:.72rem;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.035em;">{{ $label }}</span>
                <span style="font-size:.86rem;white-space:pre-wrap;">{{ $value }}</span>
            </div>
        @empty
            <p style="color:#dc2626;">No billing owner found for this project.</p>
        @endforelse
    </div>

    @if($project->ownerAccount && ! $project->ownerAccount->hasBillingDetails())
        <div style="padding:.75rem;border:1px solid rgba(220,38,38,.22);border-radius:.75rem;background:rgba(220,38,38,.06);color:#991b1b;">
            Billing details are incomplete. Ask the account owner to complete Account Center → Billing details, or update the account from the admin panel.
        </div>
    @endif
</div>
