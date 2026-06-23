<x-filament-panels::page>
    <x-ui-polish />
    <style>
        .mc-account{display:grid;gap:1rem}.mc-account-grid{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(280px,.8fr);gap:1rem}.mc-account-stack{display:grid;gap:1rem}.mc-account-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem}.mc-account-field label{display:block;margin-bottom:.35rem;color:#64748b;font-size:.66rem;font-weight:750;text-transform:uppercase;letter-spacing:.04em}.mc-account-field input,.mc-account-field select{width:100%;border:1px solid rgba(100,116,139,.26);border-radius:.65rem;background:transparent;padding:.62rem .72rem;font-size:.8rem}.mc-account-field-full{grid-column:1/-1}.mc-account-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.82rem 0;border-top:1px solid rgba(100,116,139,.14)}.mc-account-row:first-child{border-top:0;padding-top:0}.mc-switch{width:2.7rem;height:1.5rem;appearance:none;border-radius:9999px;background:#cbd5e1;position:relative;cursor:pointer;transition:.18s;flex:0 0 auto}.mc-switch:after{content:"";position:absolute;width:1.1rem;height:1.1rem;left:.2rem;top:.2rem;border-radius:9999px;background:white;box-shadow:0 1px 3px rgba(15,23,42,.25);transition:.18s}.mc-switch:checked{background:#6366f1}.mc-switch:checked:after{transform:translateX(1.2rem)}.mc-account-workspace{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.85rem 0;border-top:1px solid rgba(100,116,139,.14)}.mc-account-workspace:first-child{border-top:0}.mc-pill{display:inline-flex;align-items:center;border-radius:999px;padding:.15rem .5rem;font-size:.62rem;font-weight:750;background:rgba(99,102,241,.1);color:#4f46e5}.mc-plan-card{padding:1rem;border:1px solid rgba(99,102,241,.18);border-radius:.8rem;background:linear-gradient(135deg,rgba(99,102,241,.09),rgba(14,165,233,.06))}.mc-muted{color:#64748b}.mc-help{font-size:.72rem;line-height:1.5}.mc-account-actions{display:flex;gap:.55rem;align-items:center;flex-wrap:wrap;margin-top:1rem}@media(max-width:950px){.mc-account-grid{grid-template-columns:1fr}.mc-account-fields{grid-template-columns:1fr}}
    </style>

    <div class="mc-account">
        <div class="mc-account-grid">
            <div class="mc-account-stack">
                <x-filament::section heading="Personal details" description="These details belong to your account and follow you across all workspaces." icon="heroicon-o-identification">
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

                <x-filament::section heading="Security" description="Change your password without affecting workspace access or collaborators." icon="heroicon-o-lock-closed">
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

                <x-filament::section heading="Platform preferences" description="Personal preferences apply to your account across every workspace." icon="heroicon-o-adjustments-horizontal">
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

                    <div class="mc-account-actions">
                        <x-filament::button wire:click="savePreferences" wire:loading.attr="disabled" wire:target="savePreferences" icon="heroicon-o-check">Save preferences</x-filament::button>
                    </div>
                </x-filament::section>
            </div>

            <div class="mc-account-stack">
                <x-filament::section heading="Subscription" description="Plans are currently managed per workspace, so each organisation can grow independently." icon="heroicon-o-credit-card">
                    @if($this->currentWorkspace)
                        <div class="mc-plan-card">
                            <span class="mc-muted" style="font-size:.65rem;font-weight:750;text-transform:uppercase;">Current workspace</span>
                            <strong class="text-gray-950 dark:text-white" style="display:block;font-size:1rem;margin-top:.2rem;">{{ $this->currentWorkspace->name }}</strong>
                            <p class="mc-muted mc-help" style="margin-top:.35rem;">Active plan: <strong>{{ $this->planOptions()[$this->currentWorkspace->plan] ?? ucfirst($this->currentWorkspace->plan) }}</strong></p>
                        </div>
                    @endif

                    @if($this->manageableWorkspaces->isNotEmpty())
                        <div class="mc-account-fields" style="margin-top:1rem;">
                            <div class="mc-account-field mc-account-field-full">
                                <label for="subscription-workspace">Workspace</label>
                                <select id="subscription-workspace" wire:model.live="subscriptionWorkspaceId" class="text-gray-950 dark:text-white">
                                    @foreach($this->manageableWorkspaces as $workspace)
                                        <option value="{{ $workspace->id }}">{{ $workspace->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mc-account-field mc-account-field-full">
                                <label for="subscription-plan">Plan</label>
                                <select id="subscription-plan" wire:model="subscriptionPlan" class="text-gray-950 dark:text-white">
                                    @foreach($this->planOptions() as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mc-account-actions">
                            <x-filament::button wire:click="saveSubscriptionPlan" wire:loading.attr="disabled" wire:target="saveSubscriptionPlan" icon="heroicon-o-arrow-path">Update plan</x-filament::button>
                        </div>
                    @else
                        <p class="mc-muted mc-help">You can view your workspace plans here. Plan changes are available to workspace owners and admins.</p>
                    @endif
                </x-filament::section>

                <x-filament::section heading="Your workspaces" description="One account can belong to multiple organisations. Switch between them without changing account settings." icon="heroicon-o-building-office-2">
                    @forelse($this->workspaceRows as $workspace)
                        <div class="mc-account-workspace">
                            <div>
                                <strong class="text-gray-950 dark:text-white" style="display:block;font-size:.84rem;">{{ $workspace->name }}</strong>
                                <span class="mc-muted" style="font-size:.7rem;">{{ ucfirst($workspace->pivot->role) }} · {{ $workspace->projects_count }} project{{ $workspace->projects_count === 1 ? '' : 's' }} · {{ $this->planOptions()[$workspace->plan] ?? ucfirst($workspace->plan) }}</span>
                            </div>
                            <div style="display:flex;gap:.45rem;align-items:center;">
                                @if($this->currentWorkspace?->id === $workspace->id)
                                    <span class="mc-pill">Current</span>
                                @else
                                    <x-filament::button size="xs" color="gray" wire:click="switchWorkspace({{ $workspace->id }})">Open</x-filament::button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="mc-muted mc-help">This account is not attached to a workspace yet.</p>
                    @endforelse
                </x-filament::section>
            </div>
        </div>
    </div>
</x-filament-panels::page>
