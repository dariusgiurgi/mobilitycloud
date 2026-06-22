<x-filament-panels::page>
    <x-ui-polish />

    <style>
        .mc-team { max-width:900px;display:grid;gap:1rem; }
        .mc-team-grid { display:grid;grid-template-columns:minmax(220px,1fr) 170px auto;gap:.75rem;align-items:start; }
        .mc-team-row { display:grid;grid-template-columns:minmax(220px,1fr) 180px 42px;gap:1rem;align-items:center;padding:.9rem 0;border-top:1px solid rgba(100,116,139,.14); }
        .mc-team-avatar { width:36px;height:36px;border-radius:9999px;display:flex;align-items:center;justify-content:center;flex:none;background:rgba(99,102,241,.1);color:#4f46e5;font-size:.75rem;font-weight:750; }
        .mc-team-input { width:100%;padding:.58rem .7rem;border:1px solid rgba(100,116,139,.3);border-radius:.55rem;background:transparent;font-size:.82rem; }
        .mc-team-label { display:block;margin-bottom:.35rem;color:#64748b;font-size:.68rem;font-weight:650;text-transform:uppercase;letter-spacing:.04em; }
        .dark .mc-team-label { color:#94a3b8; }
        @media (max-width:700px) {
            .mc-team-grid { grid-template-columns:1fr; }
            .mc-team-row { grid-template-columns:minmax(0,1fr) 42px;gap:.65rem; }
            .mc-team-role { grid-column:1 / -1;grid-row:2; }
        }
    </style>

    <div class="mc-team">
        <x-filament::section heading="Invite a collaborator" description="Invitations remain valid for seven days and can be cancelled at any time.">
            <div class="mc-team-grid">
                <div>
                    <label for="team-email" class="mc-team-label">Email address</label>
                    <input id="team-email" type="email" wire:model="inviteEmail" class="mc-team-input text-gray-950 dark:text-white" placeholder="colleague@example.org">
                    @error('inviteEmail') <p style="margin-top:.3rem;color:#dc2626;font-size:.72rem;">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="team-role" class="mc-team-label">Access level</label>
                    <select id="team-role" wire:model="inviteRole" class="mc-team-input text-gray-950 dark:text-white">
                        <option value="admin">Admin</option>
                        <option value="member">Member</option>
                        <option value="viewer">Viewer</option>
                    </select>
                    @error('inviteRole') <p style="margin-top:.3rem;color:#dc2626;font-size:.72rem;">{{ $message }}</p> @enderror
                </div>
                <x-filament::button wire:click="invite" wire:loading.attr="disabled" wire:target="invite" icon="heroicon-o-paper-airplane" style="margin-top:1.25rem;">
                    Send invitation
                </x-filament::button>
            </div>

            <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.6rem;margin-top:1rem;" class="max-md:grid-cols-1">
                <div style="padding:.7rem;border-radius:.55rem;background:rgba(99,102,241,.06);font-size:.72rem;line-height:1.45;"><strong>Admin</strong><br><span class="text-gray-500 dark:text-gray-400">Manages projects, workspace details and team members.</span></div>
                <div style="padding:.7rem;border-radius:.55rem;background:rgba(99,102,241,.06);font-size:.72rem;line-height:1.45;"><strong>Member</strong><br><span class="text-gray-500 dark:text-gray-400">Creates and edits project content, without team access.</span></div>
                <div style="padding:.7rem;border-radius:.55rem;background:rgba(99,102,241,.06);font-size:.72rem;line-height:1.45;"><strong>Viewer</strong><br><span class="text-gray-500 dark:text-gray-400">Reads projects and downloads files, without changing data.</span></div>
            </div>
        </x-filament::section>

        <x-filament::section heading="Current team" :description="$this->members->count().' active '.str('member')->plural($this->members->count()).'.'">
            @foreach($this->members as $member)
                <div class="mc-team-row" wire:key="team-member-{{ $member->id }}">
                    <div style="display:flex;align-items:center;gap:.75rem;min-width:0;">
                        <span class="mc-team-avatar">{{ str($member->name)->substr(0, 2)->upper() }}</span>
                        <div style="min-width:0;">
                            <div class="text-gray-950 dark:text-white" style="font-size:.83rem;font-weight:650;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $member->name }} @if($member->id === auth()->id()) <span class="text-gray-400" style="font-weight:400;">(you)</span> @endif</div>
                            <div class="text-gray-500 dark:text-gray-400" style="font-size:.72rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $member->email }}</div>
                        </div>
                    </div>

                    <div class="mc-team-role">
                        @if($member->pivot->role === 'owner')
                            <x-filament::badge color="primary">Owner</x-filament::badge>
                        @elseif($member->id === auth()->id())
                            <x-filament::badge color="gray">{{ ucfirst($member->pivot->role) }}</x-filament::badge>
                        @else
                            <select wire:change="updateRole({{ $member->id }}, $event.target.value)" class="mc-team-input text-gray-950 dark:text-white" aria-label="Role for {{ $member->name }}">
                                @foreach(['admin' => 'Admin', 'member' => 'Member', 'viewer' => 'Viewer'] as $value => $label)
                                    <option value="{{ $value }}" @selected($member->pivot->role === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    @if($member->pivot->role !== 'owner' && $member->id !== auth()->id())
                        <x-filament::icon-button wire:click="removeMember({{ $member->id }})" wire:confirm="Remove {{ $member->name }} from this workspace?" icon="heroicon-o-trash" color="danger" size="sm" label="Remove {{ $member->name }}" />
                    @else
                        <span></span>
                    @endif
                </div>
            @endforeach
        </x-filament::section>

        @if($this->pendingInvitations->isNotEmpty())
            <x-filament::section heading="Pending invitations" :description="$this->pendingInvitations->count().' waiting for acceptance.'">
                @foreach($this->pendingInvitations as $invitation)
                    <div class="mc-team-row" wire:key="team-invitation-{{ $invitation->id }}">
                        <div style="min-width:0;">
                            <div class="text-gray-950 dark:text-white" style="font-size:.8rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $invitation->email }}</div>
                            <div class="text-gray-500 dark:text-gray-400" style="font-size:.7rem;">{{ $invitation->expires_at->isPast() ? 'Expired' : 'Expires '.$invitation->expires_at->diffForHumans() }}</div>
                        </div>
                        <x-filament::badge class="mc-team-role" :color="$invitation->expires_at->isPast() ? 'danger' : 'warning'">{{ ucfirst($invitation->role) }}</x-filament::badge>
                        <x-filament::dropdown placement="bottom-end">
                            <x-slot name="trigger"><x-filament::icon-button icon="heroicon-m-ellipsis-vertical" color="gray" label="Invitation actions" /></x-slot>
                            <x-filament::dropdown.list>
                                <x-filament::dropdown.list.item wire:click="resendInvitation({{ $invitation->id }})" icon="heroicon-m-arrow-path">Send again</x-filament::dropdown.list.item>
                                <x-filament::dropdown.list.item wire:click="cancelInvitation({{ $invitation->id }})" wire:confirm="Cancel this invitation?" icon="heroicon-m-x-mark" color="danger">Cancel invitation</x-filament::dropdown.list.item>
                            </x-filament::dropdown.list>
                        </x-filament::dropdown>
                    </div>
                @endforeach
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
