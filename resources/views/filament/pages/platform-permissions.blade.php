<x-filament-panels::page>
    <x-ui-polish />

    <style>
        .mc-admin-shell{display:grid;gap:1rem}.mc-admin-note{border:1px solid rgba(99,102,241,.16);background:rgba(99,102,241,.06);border-radius:1rem;padding:1rem;color:#4338ca;font-size:.8rem;line-height:1.55}.mc-permission-table{overflow:hidden;border:1px solid rgba(148,163,184,.18);border-radius:1rem}.mc-permission-row{display:grid;grid-template-columns:1fr 2fr 8rem 8rem;gap:0;border-top:1px solid rgba(148,163,184,.14)}.mc-permission-row:first-child{border-top:0}.mc-permission-cell{padding:.8rem .9rem;font-size:.78rem;display:flex;align-items:center}.mc-permission-head{background:rgba(148,163,184,.08);font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.04em;font-size:.65rem}.mc-area{font-weight:750;color:#111827}.dark .mc-area{color:#f9fafb}.mc-check{display:inline-flex;align-items:center;justify-content:center;border-radius:999px;padding:.22rem .55rem;font-weight:800;font-size:.66rem}.mc-yes{background:rgba(16,185,129,.1);color:#047857}.mc-no{background:rgba(148,163,184,.13);color:#64748b}@media(max-width:820px){.mc-permission-row{grid-template-columns:1fr}.mc-permission-head{display:none}.mc-permission-cell{justify-content:space-between}.mc-permission-cell::before{content:attr(data-label);font-weight:800;color:#64748b;text-transform:uppercase;font-size:.62rem;letter-spacing:.04em}.mc-area{font-size:.86rem}}
    </style>

    <div class="mc-admin-shell">
        <div class="mc-admin-note">
            This matrix is intentionally explicit. Platform admins can operate customer support and billing workflows, while platform-owner-only actions remain reserved for staff management, irreversible deletion, demo/manual commercial overrides and raw audit visibility.
        </div>

        <div class="mc-permission-table">
            <div class="mc-permission-row mc-permission-head">
                <div class="mc-permission-cell">Area</div>
                <div class="mc-permission-cell">Permission</div>
                <div class="mc-permission-cell">Owner</div>
                <div class="mc-permission-cell">Admin</div>
            </div>
            @foreach($this->permissions() as $row)
                <div class="mc-permission-row">
                    <div class="mc-permission-cell mc-area" data-label="Area">{{ $row['area'] }}</div>
                    <div class="mc-permission-cell" data-label="Permission">{{ $row['permission'] }}</div>
                    <div class="mc-permission-cell" data-label="Owner">
                        <span class="mc-check {{ $row['owner'] ? 'mc-yes' : 'mc-no' }}">{{ $row['owner'] ? 'Allowed' : 'No' }}</span>
                    </div>
                    <div class="mc-permission-cell" data-label="Admin">
                        <span class="mc-check {{ $row['admin'] ? 'mc-yes' : 'mc-no' }}">{{ $row['admin'] ? 'Allowed' : 'No' }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
