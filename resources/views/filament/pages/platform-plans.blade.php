<x-filament-panels::page>
    <x-ui-polish />

    <style>
        .mc-plans{display:grid;gap:1rem}.mc-plans-toolbar{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap}.mc-plan-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}.mc-plan-card{border:1px solid rgba(148,163,184,.18);border-radius:1rem;background:rgba(255,255,255,.02);padding:1rem;display:grid;gap:1rem}.mc-plan-top{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem}.mc-plan-badges{display:flex;gap:.4rem;flex-wrap:wrap;justify-content:flex-end}.mc-plan-badge{display:inline-flex;border-radius:999px;padding:.18rem .55rem;font-size:.62rem;font-weight:750}.mc-plan-public{background:rgba(16,185,129,.1);color:#047857}.mc-plan-internal{background:rgba(245,158,11,.13);color:#b45309}.mc-plan-recommended{background:rgba(99,102,241,.12);color:#4f46e5}.mc-plan-desc{color:#64748b;font-size:.76rem;line-height:1.55}.mc-limit-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.55rem}.mc-limit{border:1px solid rgba(148,163,184,.15);border-radius:.7rem;padding:.65rem;background:rgba(148,163,184,.04)}.mc-limit span{display:block;color:#64748b;font-size:.62rem;font-weight:750;text-transform:uppercase;letter-spacing:.04em}.mc-limit strong{display:block;margin-top:.18rem;font-size:.9rem}.mc-module-list{display:flex;flex-wrap:wrap;gap:.35rem}.mc-module{border:1px solid rgba(99,102,241,.18);border-radius:999px;padding:.22rem .52rem;font-size:.68rem;color:#4f46e5;background:rgba(99,102,241,.06)}.mc-note{padding:.85rem 1rem;border-radius:.85rem;background:rgba(14,165,233,.08);border:1px solid rgba(14,165,233,.15);font-size:.75rem;line-height:1.55;color:#0369a1}.mc-plan-actions{display:flex;gap:.45rem;flex-wrap:wrap}.mc-plan-modal-backdrop{position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:60;display:flex;align-items:flex-start;justify-content:center;padding:5vh 1rem;overflow:auto}.mc-plan-modal{width:min(980px,100%);border:1px solid rgba(148,163,184,.22);border-radius:1.15rem;background:#fff;box-shadow:0 24px 90px rgba(15,23,42,.28);overflow:hidden}.dark .mc-plan-modal{background:#111827}.mc-plan-modal-head{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;padding:1.1rem 1.2rem;border-bottom:1px solid rgba(148,163,184,.18)}.mc-plan-modal-body{padding:1.2rem;display:grid;gap:1.05rem}.mc-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.85rem}.mc-form-full{grid-column:1/-1}.mc-field label{display:block;margin-bottom:.32rem;color:#64748b;font-size:.65rem;font-weight:800;text-transform:uppercase;letter-spacing:.045em}.mc-field input,.mc-field textarea,.mc-field select{width:100%;border:1px solid rgba(100,116,139,.28);border-radius:.65rem;background:transparent;padding:.62rem .72rem;font-size:.82rem}.mc-field textarea{min-height:86px;resize:vertical}.mc-check-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.45rem}.mc-check{display:flex;align-items:center;gap:.45rem;border:1px solid rgba(148,163,184,.16);border-radius:.65rem;padding:.52rem .6rem;font-size:.76rem}.mc-check input{width:15px;height:15px;accent-color:#6366f1}.mc-error{margin-top:.25rem;color:#dc2626;font-size:.7rem}.mc-plan-modal-actions{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;padding:1rem 1.2rem;border-top:1px solid rgba(148,163,184,.18)}@media(max-width:1020px){.mc-plan-grid{grid-template-columns:1fr}.mc-check-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:620px){.mc-limit-grid,.mc-form-grid,.mc-check-grid{grid-template-columns:1fr}.mc-plan-top{display:grid}.mc-plan-badges{justify-content:flex-start}}
    </style>

    <div class="mc-plans">
        <div class="mc-plans-toolbar">
            <div class="mc-note" style="max-width:760px;">
                @if($this->canEditPlans())
                    These global plan definitions are stored in the database. Future account plan assignments use these modules, limits and prices automatically.
                @else
                    You can inspect the live plan catalogue here. Only the platform owner can edit global plan definitions.
                @endif
            </div>

            @if($this->canEditPlans())
                <x-filament::button wire:click="openCreatePlan" icon="heroicon-o-plus">
                    Create plan
                </x-filament::button>
            @endif
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

                        <div class="mc-limit">
                            <span>Price</span>
                            <strong class="text-gray-950 dark:text-white">{{ $this->priceValue($plan['monthly_price'], $plan['yearly_price'], $plan['currency']) }}</strong>
                        </div>

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

                        @if($this->canEditPlans())
                            <div class="mc-plan-actions">
                                <x-filament::button wire:click="openEditPlan('{{ $plan['key'] }}')" size="sm" icon="heroicon-o-pencil-square">
                                    Edit plan
                                </x-filament::button>
                                @if(array_key_exists($plan['key'], \App\Support\PlanCatalog::codedPlans()))
                                    <x-filament::button wire:click="resetPlanToCodeDefaults('{{ $plan['key'] }}')" wire:confirm="Reset this plan to the built-in defaults?" color="gray" size="sm" icon="heroicon-o-arrow-path">
                                        Reset defaults
                                    </x-filament::button>
                                @endif
                            </div>
                        @endif
                    </div>
                </x-filament::section>
            @endforeach
        </div>
    </div>

    @if($showPlanModal)
        <div class="mc-plan-modal-backdrop" wire:click.self="closePlanModal">
            <div class="mc-plan-modal">
                <div class="mc-plan-modal-head">
                    <div>
                        <h2 class="text-gray-950 dark:text-white" style="font-size:1.05rem;font-weight:800;margin:0;">{{ $editingPlanKey ? 'Edit plan' : 'Create plan' }}</h2>
                        <p class="text-gray-500 dark:text-gray-400" style="font-size:.78rem;margin:.22rem 0 0;">Global entitlements used when accounts receive this plan.</p>
                    </div>
                    <x-filament::icon-button wire:click="closePlanModal" icon="heroicon-o-x-mark" color="gray" label="Close" />
                </div>

                <div class="mc-plan-modal-body">
                    <div class="mc-form-grid">
                        <div class="mc-field">
                            <label for="plan-key">Plan key</label>
                            <input id="plan-key" wire:model="form.key" placeholder="writer_pro" @disabled((bool) $editingPlanKey) class="text-gray-950 dark:text-white">
                            @error('form.key') <div class="mc-error">{{ $message }}</div> @enderror
                        </div>
                        <div class="mc-field">
                            <label for="plan-label">Label</label>
                            <input id="plan-label" wire:model="form.label" placeholder="Writer Pro" class="text-gray-950 dark:text-white">
                            @error('form.label') <div class="mc-error">{{ $message }}</div> @enderror
                        </div>
                        <div class="mc-field">
                            <label for="plan-visibility">Visibility</label>
                            <select id="plan-visibility" wire:model="form.visibility" class="text-gray-950 dark:text-white">
                                <option value="public">Public</option>
                                <option value="internal">Internal</option>
                            </select>
                            @error('form.visibility') <div class="mc-error">{{ $message }}</div> @enderror
                        </div>
                        <div class="mc-field">
                            <label for="plan-currency">Currency</label>
                            <input id="plan-currency" wire:model="form.currency" maxlength="3" placeholder="EUR" class="text-gray-950 dark:text-white">
                            @error('form.currency') <div class="mc-error">{{ $message }}</div> @enderror
                        </div>
                        <div class="mc-field">
                            <label for="plan-monthly">Monthly price</label>
                            <input id="plan-monthly" type="number" min="0" step="0.01" wire:model="form.monthly_price" placeholder="0.00" class="text-gray-950 dark:text-white">
                            @error('form.monthly_price') <div class="mc-error">{{ $message }}</div> @enderror
                        </div>
                        <div class="mc-field">
                            <label for="plan-yearly">Yearly price</label>
                            <input id="plan-yearly" type="number" min="0" step="0.01" wire:model="form.yearly_price" placeholder="0.00" class="text-gray-950 dark:text-white">
                            @error('form.yearly_price') <div class="mc-error">{{ $message }}</div> @enderror
                        </div>
                        <div class="mc-field mc-form-full">
                            <label for="plan-description">Description</label>
                            <textarea id="plan-description" wire:model="form.description" class="text-gray-950 dark:text-white"></textarea>
                            @error('form.description') <div class="mc-error">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mc-form-grid">
                        @foreach($this->limitOptions() as $key => $label)
                            <div class="mc-field">
                                <label for="limit-{{ $key }}">{{ $label }}</label>
                                <input id="limit-{{ $key }}" type="number" min="0" step="1" wire:model="form.limits.{{ $key }}" class="text-gray-950 dark:text-white">
                                @error("form.limits.{$key}") <div class="mc-error">{{ $message }}</div> @enderror
                            </div>
                        @endforeach
                    </div>

                    <div>
                        <div class="text-gray-950 dark:text-white" style="font-size:.8rem;font-weight:750;margin-bottom:.55rem;">Enabled modules</div>
                        <div class="mc-check-grid">
                            @foreach($this->moduleOptions() as $key => $label)
                                <label class="mc-check text-gray-700 dark:text-gray-300">
                                    <input type="checkbox" wire:model="form.modules" value="{{ $key }}">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        @error('form.modules') <div class="mc-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="mc-check-grid">
                        <label class="mc-check text-gray-700 dark:text-gray-300">
                            <input type="checkbox" wire:model="form.recommended">
                            Mark as recommended
                        </label>
                        <label class="mc-check text-gray-700 dark:text-gray-300">
                            <input type="checkbox" wire:model="form.is_active">
                            Active plan
                        </label>
                        <label class="mc-check text-gray-700 dark:text-gray-300">
                            <input type="checkbox" wire:model="syncExistingAccounts">
                            Sync existing accounts on this plan
                        </label>
                    </div>
                </div>

                <div class="mc-plan-modal-actions">
                    <p class="text-gray-500 dark:text-gray-400" style="font-size:.74rem;margin:0;max-width:620px;">
                        Without sync, existing accounts keep their current module/limit snapshot. Future plan assignments use the updated global definition.
                    </p>
                    <div style="display:flex;gap:.5rem;">
                        <x-filament::button wire:click="closePlanModal" color="gray">Cancel</x-filament::button>
                        <x-filament::button wire:click="savePlan" wire:loading.attr="disabled" wire:target="savePlan" icon="heroicon-o-check">
                            Save plan
                        </x-filament::button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
