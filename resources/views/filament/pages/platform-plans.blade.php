<x-filament-panels::page>
    <x-ui-polish />

    <style>
        .mc-plans{display:grid;gap:1rem}.mc-plan-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}.mc-plan-card{border:1px solid rgba(148,163,184,.18);border-radius:1rem;background:rgba(255,255,255,.02);padding:1rem;display:grid;gap:1rem}.mc-plan-top{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem}.mc-plan-badges{display:flex;gap:.4rem;flex-wrap:wrap;justify-content:flex-end}.mc-plan-badge{display:inline-flex;border-radius:999px;padding:.18rem .55rem;font-size:.62rem;font-weight:750}.mc-plan-public{background:rgba(16,185,129,.1);color:#047857}.mc-plan-internal{background:rgba(245,158,11,.13);color:#b45309}.mc-plan-recommended{background:rgba(99,102,241,.12);color:#4f46e5}.mc-plan-desc{color:#64748b;font-size:.76rem;line-height:1.55}.mc-limit-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.55rem}.mc-limit{border:1px solid rgba(148,163,184,.15);border-radius:.7rem;padding:.65rem;background:rgba(148,163,184,.04)}.mc-limit span{display:block;color:#64748b;font-size:.62rem;font-weight:750;text-transform:uppercase;letter-spacing:.04em}.mc-limit strong{display:block;margin-top:.18rem;font-size:.9rem}.mc-module-list{display:flex;flex-wrap:wrap;gap:.35rem}.mc-module{border:1px solid rgba(99,102,241,.18);border-radius:999px;padding:.22rem .52rem;font-size:.68rem;color:#4f46e5;background:rgba(99,102,241,.06)}.mc-note{padding:.85rem 1rem;border-radius:.85rem;background:rgba(14,165,233,.08);border:1px solid rgba(14,165,233,.15);font-size:.75rem;line-height:1.55;color:#0369a1}@media(max-width:1020px){.mc-plan-grid{grid-template-columns:1fr}}@media(max-width:620px){.mc-limit-grid{grid-template-columns:1fr 1fr}.mc-plan-top{display:grid}.mc-plan-badges{justify-content:flex-start}}
    </style>

    <div class="mc-plans">
        <div class="mc-note">
            These plans currently come from the application catalogue, not from the billing provider. That keeps entitlements deterministic while Stripe/payment integration is still pending.
        </div>

        <div class="mc-plan-grid">
            @foreach($this->plans() as $plan)
                <x-filament::section>
                    <div class="mc-plan-card">
                        <div class="mc-plan-top">
                            <div>
                                <div class="text-gray-950 dark:text-white" style="font-size:1.05rem;font-weight:750;">{{ $plan['label'] }}</div>
                                <div class="text-gray-500 dark:text-gray-400" style="font-size:.68rem;margin-top:.12rem;">{{ $plan['key'] }}</div>
                            </div>
                            <div class="mc-plan-badges">
                                <span class="mc-plan-badge {{ $plan['visibility'] === 'internal' ? 'mc-plan-internal' : 'mc-plan-public' }}">{{ ucfirst($plan['visibility']) }}</span>
                                @if($plan['recommended'])
                                    <span class="mc-plan-badge mc-plan-recommended">Recommended</span>
                                @endif
                            </div>
                        </div>

                        @if($plan['description'])
                            <p class="mc-plan-desc">{{ $plan['description'] }}</p>
                        @endif

                        <div>
                            <div class="text-gray-950 dark:text-white" style="font-size:.78rem;font-weight:700;margin-bottom:.55rem;">Limits</div>
                            <div class="mc-limit-grid">
                                @foreach($plan['limits'] as $key => $value)
                                    <div class="mc-limit">
                                        <span>{{ $this->limitLabel($key) }}</span>
                                        <strong class="text-gray-950 dark:text-white">{{ $this->limitValue($key, $value) }}</strong>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <div class="text-gray-950 dark:text-white" style="font-size:.78rem;font-weight:700;margin-bottom:.55rem;">Included modules · {{ count($plan['modules']) }}/{{ count($this->moduleOptions()) }}</div>
                            <div class="mc-module-list">
                                @foreach($plan['modules'] as $module)
                                    <span class="mc-module">{{ $module['label'] }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </x-filament::section>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
