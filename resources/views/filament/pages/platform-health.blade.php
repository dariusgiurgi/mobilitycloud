<x-filament-panels::page>
    <x-ui-polish />

    <style>
        .mc-health{display:grid;gap:1rem}.mc-health-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}.mc-health-card{border:1px solid rgba(148,163,184,.18);border-radius:1rem;padding:1rem;background:rgba(255,255,255,.02);display:grid;gap:.55rem}.mc-health-top{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem}.mc-health-label{font-weight:800;color:#111827}.dark .mc-health-label{color:#fff}.mc-health-detail{font-size:.76rem;color:#64748b;line-height:1.45}.mc-health-badge{border-radius:999px;padding:.2rem .58rem;font-size:.66rem;font-weight:850}.mc-health-ok{background:rgba(16,185,129,.1);color:#047857}.mc-health-warn{background:rgba(245,158,11,.13);color:#b45309}.mc-health-bad{background:rgba(239,68,68,.12);color:#dc2626}.mc-health-note{border:1px solid rgba(14,165,233,.15);background:rgba(14,165,233,.08);border-radius:1rem;padding:1rem;color:#0369a1;font-size:.8rem;line-height:1.55}@media(max-width:900px){.mc-health-grid{grid-template-columns:1fr}}
    </style>

    <div class="mc-health">
        <div class="mc-health-note">
            These checks are intentionally lightweight: they tell an admin where to look first. They do not replace external uptime monitoring, queue supervisor monitoring or billing-provider webhooks.
        </div>

        <div class="mc-health-grid">
            @foreach($this->checks() as $check)
                <x-filament::section>
                    <div class="mc-health-card">
                        <div class="mc-health-top">
                            <div>
                                <div class="mc-health-label">{{ $check['label'] }}</div>
                                <div class="text-gray-500 dark:text-gray-400" style="font-size:.72rem;margin-top:.16rem;">{{ $check['status'] }}</div>
                            </div>
                            <span class="mc-health-badge mc-health-{{ $check['level'] }}">
                                {{ $check['level'] === 'ok' ? 'OK' : ($check['level'] === 'warn' ? 'Review' : 'Issue') }}
                            </span>
                        </div>
                        <div class="mc-health-detail">{{ $check['detail'] }}</div>
                    </div>
                </x-filament::section>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
