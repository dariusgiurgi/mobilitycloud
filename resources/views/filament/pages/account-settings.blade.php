<x-filament-panels::page>
    <x-ui-polish />
    <style>
        .mc-account{display:grid;gap:1rem}.mc-account-grid{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(280px,.8fr);gap:1rem}.mc-account-stack{display:grid;gap:1rem}.mc-account-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem}.mc-account-field label{display:block;margin-bottom:.35rem;color:#64748b;font-size:.66rem;font-weight:750;text-transform:uppercase;letter-spacing:.04em}.mc-account-field input,.mc-account-field select,.mc-account-field textarea{width:100%;border:1px solid rgba(100,116,139,.26);border-radius:.65rem;background:transparent;padding:.62rem .72rem;font-size:.8rem}.mc-account-field textarea{min-height:6rem;resize:vertical}.mc-account-field-full{grid-column:1/-1}.mc-account-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.82rem 0;border-top:1px solid rgba(100,116,139,.14)}.mc-account-row:first-child{border-top:0;padding-top:0}.mc-switch{width:2.7rem;height:1.5rem;appearance:none;border-radius:9999px;background:#cbd5e1;position:relative;cursor:pointer;transition:.18s;flex:0 0 auto}.mc-switch:after{content:"";position:absolute;width:1.1rem;height:1.1rem;left:.2rem;top:.2rem;border-radius:9999px;background:white;box-shadow:0 1px 3px rgba(15,23,42,.25);transition:.18s}.mc-switch:checked{background:#6366f1}.mc-switch:checked:after{transform:translateX(1.2rem)}.mc-pill{display:inline-flex;align-items:center;border-radius:999px;padding:.15rem .5rem;font-size:.62rem;font-weight:750;background:rgba(99,102,241,.1);color:#4f46e5}.mc-plan-card{padding:1rem;border:1px solid rgba(99,102,241,.18);border-radius:.8rem;background:linear-gradient(135deg,rgba(99,102,241,.09),rgba(14,165,233,.06))}.mc-muted{color:#64748b}.mc-help{font-size:.72rem;line-height:1.5}.mc-account-actions{display:flex;gap:.55rem;align-items:center;flex-wrap:wrap;margin-top:1rem}@media(max-width:950px){.mc-account-grid{grid-template-columns:1fr}.mc-account-fields{grid-template-columns:1fr}}
    </style>

    <div class="mc-account">
        <div class="mc-account-grid">
            <div class="mc-account-stack">
                <x-filament::section heading="Personal details" description="These details belong to your account and follow you across every project." icon="heroicon-o-identification">
                    <div class="mc-account-fields">
                        <div class="mc-account-field">
                            <label for="account-name">Name</label>
                            <input id="account-name" type="text" wire:model="name" class="text-gray-950 dark:text-white">
                            @error('name')<p class="mc-muted" style="font-size:.68rem;margin-top:.25rem;color:#dc2626;">{{ $message }}</p>@enderror
                        </div>
                        <div class="mc-account-field">
                            <label for="account-email">Email</label>
                            <input id="account-email" type="email" wire:model="email" class="text-gray-950 dark:text-white">
                            @error('email')<p class="mc-muted" style="font-size:.68rem;margin-top:.25rem;color:#dc2626;">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="mc-account-actions">
                        <x-filament::button wire:click="saveProfile" wire:loading.attr="disabled" wire:target="saveProfile" icon="heroicon-o-check">Save profile</x-filament::button>
                    </div>
                </x-filament::section>

                <x-filament::section heading="Security" description="Change your password without affecting project access or collaborators." icon="heroicon-o-lock-closed">
                    <div class="mc-account-fields">
                        <div class="mc-account-field mc-account-field-full">
                            <label for="current-password">Current password</label>
                            <input id="current-password" type="password" wire:model="currentPassword" autocomplete="current-password" class="text-gray-950 dark:text-white">
                            @error('currentPassword')<p style="font-size:.68rem;margin-top:.25rem;color:#dc2626;">{{ $message }}</p>@enderror
                        </div>
                        <div class="mc-account-field">
                            <label for="new-password">New password</label>
                            <input id="new-password" type="password" wire:model="newPassword" autocomplete="new-password" class="text-gray-950 dark:text-white">
                            @error('newPassword')<p style="font-size:.68rem;margin-top:.25rem;color:#dc2626;">{{ $message }}</p>@enderror
                        </div>
                        <div class="mc-account-field">
                            <label for="new-password-confirmation">Confirm new password</label>
                            <input id="new-password-confirmation" type="password" wire:model="newPasswordConfirmation" autocomplete="new-password" class="text-gray-950 dark:text-white">
                        </div>
                    </div>
                    <div class="mc-account-actions">
                        <x-filament::button wire:click="updatePassword" wire:loading.attr="disabled" wire:target="updatePassword" icon="heroicon-o-key">Update password</x-filament::button>
                    </div>
                </x-filament::section>

                @unless($this->isPlatformPanel())
                    <x-filament::section heading="Billing details" description="Required before creating owned projects. These are the fiscal details used for approved-project invoices." icon="heroicon-o-building-office-2">
                        <div class="mc-account-fields">
                            <div class="mc-account-field">
                                <label for="billing-name">Billing name</label>
                                <input id="billing-name" type="text" wire:model="billingName" class="text-gray-950 dark:text-white" placeholder="Organisation or person">
                                @error('billingName')<p style="font-size:.68rem;margin-top:.25rem;color:#dc2626;">{{ $message }}</p>@enderror
                            </div>
                            <div class="mc-account-field">
                                <label for="billing-vat">VAT / registration</label>
                                <input id="billing-vat" type="text" wire:model="billingVat" class="text-gray-950 dark:text-white" placeholder="Optional">
                                @error('billingVat')<p style="font-size:.68rem;margin-top:.25rem;color:#dc2626;">{{ $message }}</p>@enderror
                            </div>
                            <div class="mc-account-field">
                                <label for="billing-country">Country</label>
                                <input id="billing-country" type="text" wire:model="billingCountry" class="text-gray-950 dark:text-white" placeholder="Romania">
                                @error('billingCountry')<p style="font-size:.68rem;margin-top:.25rem;color:#dc2626;">{{ $message }}</p>@enderror
                            </div>
                            <div class="mc-account-field mc-account-field-full">
                                <label for="billing-address">Billing address</label>
                                <textarea id="billing-address" wire:model="billingAddress" class="text-gray-950 dark:text-white" placeholder="Street, number, city, postal code"></textarea>
                                @error('billingAddress')<p style="font-size:.68rem;margin-top:.25rem;color:#dc2626;">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div class="mc-account-actions">
                            <x-filament::button wire:click="saveBillingDetails" wire:loading.attr="disabled" wire:target="saveBillingDetails" icon="heroicon-o-check">Save billing details</x-filament::button>
                        </div>
                    </x-filament::section>
                @endunless

                <x-filament::section
                    heading="{{ $this->isPlatformPanel() ? 'Platform admin preferences' : 'Platform preferences' }}"
                    description="{{ $this->isPlatformPanel() ? 'These preferences apply only to the internal platform administration panel.' : 'Personal preferences apply to your account across every project.' }}"
                    icon="heroicon-o-adjustments-horizontal"
                >
                    <div class="mc-account-fields">
                        <div class="mc-account-field">
                            <label for="default-landing">Default landing</label>
                            <select id="default-landing" wire:model="defaultLanding" class="text-gray-950 dark:text-white">
                                @foreach($this->landingOptions() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mc-account-field">
                            <label for="interface-density">Interface density</label>
                            <select id="interface-density" wire:model="interfaceDensity" class="text-gray-950 dark:text-white">
                                @foreach($this->densityOptions() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @unless($this->isPlatformPanel())
                        <div style="margin-top:1rem;">
                            @foreach([
                                ['taskAssigned','New task assigned','When another collaborator assigns a task to you.'],
                                ['taskDueSoon','Deadline approaching','One reminder when an open task is due within three days.'],
                                ['taskOverdue','Task overdue','One alert after an assigned task passes its deadline.'],
                            ] as [$model,$label,$detail])
                                <label class="mc-account-row">
                                    <span>
                                        <span class="text-gray-950 dark:text-white" style="display:block;font-size:.82rem;font-weight:650;">{{ $label }}</span>
                                        <span class="mc-muted" style="display:block;font-size:.71rem;margin-top:.18rem;">{{ $detail }}</span>
                                    </span>
                                    <input type="checkbox" wire:model="{{ $model }}" class="mc-switch" aria-label="{{ $label }}">
                                </label>
                            @endforeach
                        </div>
                    @endunless

                    <div class="mc-account-actions">
                        <x-filament::button wire:click="savePreferences" wire:loading.attr="disabled" wire:target="savePreferences" icon="heroicon-o-check">Save preferences</x-filament::button>
                    </div>
                </x-filament::section>
            </div>

            @unless($this->isPlatformPanel())
                <div class="mc-account-stack">
                    <x-filament::section
                        heading="Account access"
                        :description="$this->currentAccount?->isUnlimitedAccount() ? 'Access is attached to this email account. Unlimited accounts have full access without project administration fees.' : 'Access is attached to this email account. Approved projects are billed separately through manual fiscal invoices.'"
                        icon="heroicon-o-credit-card"
                    >
                        @if($this->currentAccount)
                            <div class="mc-plan-card">
                                <span class="mc-muted" style="font-size:.65rem;font-weight:750;text-transform:uppercase;">Current access</span>
                                <strong class="text-gray-950 dark:text-white" style="display:block;font-size:1rem;margin-top:.2rem;">{{ $this->currentAccount->isUnlimitedAccount() ? 'Unlimited' : 'Standard' }}</strong>
                                <p class="mc-muted mc-help" style="margin-top:.35rem;">
                                    @if($this->currentAccount->isUnlimitedAccount())
                                        Unlimited accounts are owner-granted exceptions with full access and no project administration fees.
                                    @else
                                        Standard accounts can use the platform and are billed manually after projects are approved.
                                    @endif
                                </p>
                            </div>
                        @endif
                    </x-filament::section>
                </div>
            @endunless
        </div>
    </div>
</x-filament-panels::page>
