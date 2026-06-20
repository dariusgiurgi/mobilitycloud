<x-filament-panels::page>
    @php
        $participants = $this->getParticipants();
        $roles = $this->getRoles();
        $partnerOrgs = $this->getPartnerOrgs();
    @endphp

    <div class="mc-part">

    {{-- Toolbar --}}
    <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;flex-wrap:wrap;">
        <a href="{{ \App\Filament\Resources\Projects\ProjectResource::getUrl('overview', ['record' => $record]) }}"
           class="text-gray-700 dark:text-gray-200"
           style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:8px;border:1px solid rgba(100,116,139,.3);text-decoration:none;font-size:13px;">
            ← Overview
        </a>
        <div style="flex:1;"></div>
        <span class="text-gray-500 dark:text-gray-400" style="font-size:13px;">{{ $participants->count() }} participants</span>
        <button type="button" wire:click="openCreate"
                style="padding:8px 16px;border-radius:8px;border:none;background:#6366f1;color:#fff;cursor:pointer;font-size:13px;font-weight:600;">
            + Add participant
        </button>
    </div>

    {{-- List --}}
    @if($participants->isEmpty())
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="padding:2.5rem;text-align:center;">
            <p class="text-gray-500 dark:text-gray-400" style="font-size:14px;margin:0 0 1rem;">No participants yet.</p>
            <button type="button" wire:click="openCreate" style="padding:8px 16px;border-radius:8px;border:none;background:#6366f1;color:#fff;cursor:pointer;font-size:13px;font-weight:500;">+ Add the first one</button>
        </div>
    @else
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="overflow:hidden;">
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:13px;min-width:760px;">
                    <thead>
                        <tr class="text-gray-500 dark:text-gray-400" style="background:rgba(100,116,139,.06);font-size:10px;text-transform:uppercase;letter-spacing:.04em;">
                            <th style="padding:10px 12px;text-align:left;">Name</th>
                            <th style="padding:10px 12px;text-align:left;">Role</th>
                            <th style="padding:10px 12px;text-align:left;">Country</th>
                            <th style="padding:10px 12px;text-align:center;">Age</th>
                            <th style="padding:10px 12px;text-align:center;">Flags</th>
                            <th style="padding:10px 12px;text-align:center;">GDPR</th>
                            <th style="padding:10px 12px;text-align:center;width:90px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($participants as $p)
                        <tr wire:key="part-{{ $p->id }}" class="text-gray-950 dark:text-white" style="border-top:1px solid rgba(100,116,139,.12);">
                            <td style="padding:9px 12px;font-weight:600;">{{ $p->fullName() }}</td>
                            <td style="padding:9px 12px;" class="text-gray-500 dark:text-gray-400">{{ $p->roleLabel() }}</td>
                            <td style="padding:9px 12px;" class="text-gray-500 dark:text-gray-400">{{ $p->country ?: '—' }}</td>
                            <td style="padding:9px 12px;text-align:center;">
                                {{ $p->ageAtReference() ?? '—' }}
                                @if($p->isMinor())
                                    <span title="Minor — needs parental consent" style="display:inline-block;margin-left:4px;font-size:10px;font-weight:700;padding:1px 6px;border-radius:999px;background:rgba(245,158,11,.18);color:#d97706;">MINOR</span>
                                @endif
                            </td>
                            <td style="padding:9px 12px;text-align:center;">
                                @if($p->fewer_opportunities)
                                    <span title="Fewer opportunities" style="font-size:10px;font-weight:700;padding:1px 6px;border-radius:999px;background:rgba(99,102,241,.15);color:#6366f1;">FO</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td style="padding:9px 12px;text-align:center;">
                                <button type="button" wire:click="setGdprConsent({{ $p->id }})"
                                        title="{{ $p->gdpr_consented_at ? 'Consented '.$p->gdpr_consented_at->format('d M Y') : 'Not consented — click to mark' }}"
                                        style="border:none;background:transparent;cursor:pointer;font-size:16px;line-height:1;color:{{ $p->gdpr_consented_at ? '#16a34a' : '#cbd5e1' }};">
                                    {{ $p->gdpr_consented_at ? '✔' : '○' }}
                                </button>
                            </td>
                            <td style="padding:9px 12px;text-align:center;">
                                <button type="button" wire:click="openEdit({{ $p->id }})" title="Edit"
                                        style="width:28px;height:28px;border:none;background:transparent;cursor:pointer;color:#9ca3af;border-radius:6px;"
                                        onmouseover="this.style.background='rgba(99,102,241,.1)';this.style.color='#6366f1';"
                                        onmouseout="this.style.background='transparent';this.style.color='#9ca3af';">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline;"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg>
                                </button>
                                <button type="button" wire:click="deleteParticipant({{ $p->id }})" wire:confirm="Remove this participant?" title="Remove"
                                        style="width:28px;height:28px;border:none;background:transparent;cursor:pointer;color:#9ca3af;border-radius:6px;"
                                        onmouseover="this.style.background='rgba(239,68,68,.1)';this.style.color='#dc2626';"
                                        onmouseout="this.style.background='transparent';this.style.color='#9ca3af';">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline;"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    </div>

    {{-- ── Add/Edit modal ── --}}
    @if($showModal)
    <div style="position:fixed;inset:0;z-index:50;display:flex;align-items:flex-start;justify-content:center;padding:2.5rem 1rem;background:rgba(0,0,0,.55);"
         wire:click.self="closeModal">
        <div class="mc-part-modal"
             style="width:100%;max-width:640px;max-height:86vh;overflow-y:auto;border-radius:14px;padding:1.5rem;box-shadow:0 20px 50px rgba(0,0,0,.4);background:#ffffff;color:#18181b;">

            <h3 style="font-size:17px;font-weight:600;margin:0 0 1.25rem;">{{ $editingId ? 'Edit participant' : 'Add participant' }}</h3>

            {{-- Identity --}}
            <p class="mc-part-sec">Identity</p>
            <div class="mc-part-grid">
                <div>
                    <label class="mc-part-lbl">First name *</label>
                    <input type="text" wire:model="data.first_name" class="mc-part-in">
                    @error('data.first_name') <span class="mc-part-err">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mc-part-lbl">Last name *</label>
                    <input type="text" wire:model="data.last_name" class="mc-part-in">
                    @error('data.last_name') <span class="mc-part-err">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mc-part-lbl">Birth date</label>
                    <input type="date" wire:model="data.birth_date" class="mc-part-in">
                </div>
                <div>
                    <label class="mc-part-lbl">Gender</label>
                    <select wire:model="data.gender" class="mc-part-in">
                        <option value="">—</option>
                        <option value="female">Female</option>
                        <option value="male">Male</option>
                        <option value="other">Other</option>
                        <option value="undisclosed">Prefer not to say</option>
                    </select>
                </div>
                <div>
                    <label class="mc-part-lbl">Nationality</label>
                    <input type="text" wire:model="data.nationality" class="mc-part-in">
                </div>
                <div>
                    <label class="mc-part-lbl">Role</label>
                    <select wire:model="data.role" class="mc-part-in">
                        @foreach($roles as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Belonging --}}
            <p class="mc-part-sec">Belonging</p>
            <div class="mc-part-grid">
                <div>
                    <label class="mc-part-lbl">Organisation / group</label>
                    @if(count($partnerOrgs) > 0)
                        <select wire:model="data.partner_organisation" class="mc-part-in">
                            <option value="">—</option>
                            @foreach($partnerOrgs as $org)
                                <option value="{{ $org['name'] }}">{{ $org['label'] }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" wire:model="data.partner_organisation" class="mc-part-in"
                               placeholder="Add organisations in the Application first">
                    @endif
                </div>
                <div>
                    <label class="mc-part-lbl">Country</label>
                    <input type="text" wire:model="data.country" class="mc-part-in">
                </div>
            </div>

            {{-- Contact --}}
            <p class="mc-part-sec">Contact</p>
            <div class="mc-part-grid">
                <div>
                    <label class="mc-part-lbl">Email</label>
                    <input type="email" wire:model="data.email" class="mc-part-in">
                    @error('data.email') <span class="mc-part-err">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="mc-part-lbl">Phone</label>
                    <input type="text" wire:model="data.phone" class="mc-part-in">
                </div>
                <div style="grid-column:1 / -1;">
                    <label class="mc-part-lbl">Address</label>
                    <input type="text" wire:model="data.address" class="mc-part-in">
                </div>
            </div>

            {{-- Sensitive --}}
            <p class="mc-part-sec">Sensitive (GDPR)</p>
            <div class="mc-part-grid">
                <div>
                    <label class="mc-part-lbl">Medical conditions</label>
                    <input type="text" wire:model="data.medical_conditions" class="mc-part-in">
                </div>
                <div>
                    <label class="mc-part-lbl">Allergies</label>
                    <input type="text" wire:model="data.allergies" class="mc-part-in">
                </div>
                <div>
                    <label class="mc-part-lbl">Dietary restrictions</label>
                    <input type="text" wire:model="data.dietary_restrictions" class="mc-part-in">
                </div>
                <div>
                    <label class="mc-part-lbl">Special needs</label>
                    <input type="text" wire:model="data.special_needs" class="mc-part-in">
                </div>
                <div style="grid-column:1 / -1;">
                    <label style="display:inline-flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">
                        <input type="checkbox" wire:model="data.fewer_opportunities" style="accent-color:#6366f1;">
                        Fewer opportunities
                    </label>
                </div>
            </div>

            {{-- Guardian --}}
            <p class="mc-part-sec">Legal guardian (for minors)</p>
            <div class="mc-part-grid">
                <div>
                    <label class="mc-part-lbl">Guardian name</label>
                    <input type="text" wire:model="data.guardian_name" class="mc-part-in">
                </div>
                <div>
                    <label class="mc-part-lbl">Guardian contact</label>
                    <input type="text" wire:model="data.guardian_contact" class="mc-part-in">
                </div>
            </div>

            {{-- Documents (doar la editare, cand participantul exista) --}}
            @if($editingId)
                @php $atts = $this->attachmentsFor($editingId); $docTypes = $this->getDocTypes(); @endphp
                <p class="mc-part-sec">Documents</p>

                <div style="display:flex;flex-direction:column;gap:6px;margin-bottom:1rem;">
                    @foreach($docTypes as $typeKey => $typeLabel)
                        @php $att = $atts->get($typeKey); @endphp
                        <div style="display:flex;align-items:center;gap:.6rem;padding:8px 11px;border:1px solid rgba(100,116,139,.2);border-radius:8px;">
                            <span class="text-gray-700 dark:text-gray-200" style="font-size:12px;font-weight:600;min-width:150px;">{{ $typeLabel }}</span>

                            @if($att)
                                <a href="{{ $att->url() }}" target="_blank"
                                   class="text-gray-500 dark:text-gray-400"
                                   style="font-size:12px;text-decoration:none;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                                   title="{{ $att->original_name }}">
                                    📄 {{ \Illuminate\Support\Str::afterLast($att->path, '/') }} <span style="opacity:.6;">({{ $att->humanSize() }})</span>
                                </a>
                                <button type="button" wire:click="deleteAttachment({{ $att->id }})"
                                        title="Remove" style="border:none;background:transparent;cursor:pointer;color:#9ca3af;font-size:14px;"
                                        onmouseover="this.style.color='#dc2626';" onmouseout="this.style.color='#9ca3af';">✕</button>
                            @else
                                <span class="text-gray-400" style="font-size:12px;flex:1;">— not uploaded</span>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Upload row --}}
                <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;padding:10px 11px;border:1px dashed rgba(100,116,139,.35);border-radius:8px;margin-bottom:.5rem;"
                     wire:key="upload-row-{{ $editingId }}">
                    <select wire:model="uploadType" class="mc-part-in" style="width:auto;min-width:160px;">
                        @foreach($docTypes as $typeKey => $typeLabel)
                            <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                        @endforeach
                    </select>

                    <input type="file" wire:model="uploadFile"
                           class="text-gray-600 dark:text-gray-300" style="font-size:12px;flex:1;min-width:160px;">

                    <button type="button" wire:click="uploadAttachment"
                            wire:loading.attr="disabled" wire:target="uploadAttachment,uploadFile"
                            style="padding:7px 14px;border-radius:7px;border:none;background:#6366f1;color:#fff;cursor:pointer;font-size:12px;font-weight:600;">
                        <span wire:loading.remove wire:target="uploadAttachment,uploadFile">Upload</span>
                        <span wire:loading wire:target="uploadAttachment,uploadFile">…</span>
                    </button>
                </div>
                @error('uploadFile') <span class="mc-part-err" style="margin-bottom:.5rem;">{{ $message }}</span> @enderror
                <p class="text-gray-400" style="font-size:11px;margin:0 0 .5rem;">Max 10 MB. Uploading the same type replaces the previous file.</p>
            @else
                <p class="mc-part-sec">Documents</p>
                <p class="text-gray-400" style="font-size:12px;margin-bottom:1rem;">Save the participant first, then reopen to attach documents.</p>
            @endif

            <div style="display:flex;justify-content:flex-end;gap:.5rem;margin-top:1.5rem;">
                <button type="button" wire:click="closeModal"
                        style="padding:9px 18px;border-radius:8px;border:1px solid rgba(100,116,139,.3);background:transparent;cursor:pointer;font-size:13px;">Cancel</button>
                <button type="button" wire:click="save"
                        style="padding:9px 18px;border-radius:8px;border:none;background:#6366f1;color:#fff;cursor:pointer;font-size:13px;font-weight:600;">Save</button>
            </div>
        </div>
    </div>
    @endif

    <style>
        .mc-part-sec { font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#71717a;margin:1.25rem 0 .6rem; }
        .mc-part-sec:first-of-type { margin-top:0; }
        .mc-part-grid { display:grid;grid-template-columns:1fr 1fr;gap:.85rem; }
        .mc-part-lbl { display:block;font-size:11px;font-weight:600;color:#71717a;margin-bottom:4px; }
        .mc-part-in { width:100%;padding:8px 11px;border:1px solid rgba(100,116,139,.3);border-radius:7px;background:#fafafa;color:#18181b;font-size:13px; }
        .mc-part-err { display:block;color:#dc2626;font-size:11px;margin-top:3px; }

        .dark .mc-part-modal { background:#18212f !important; color:#f4f4f5 !important; }
        .dark .mc-part-sec { color:#a1a1aa; }
        .dark .mc-part-lbl { color:#a1a1aa; }
        .dark .mc-part-in { background:#27303f !important; color:#f4f4f5 !important; border-color:rgba(148,163,184,.3) !important; }
        .dark .mc-part-in option { background:#27303f; color:#f4f4f5; }
    </style>
</x-filament-panels::page>