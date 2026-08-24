<x-filament-panels::page>
    <x-ui-polish />
    @php
        $forms = $this->forms;
        $campaigns = $this->campaigns;
        $mobilities = $this->availableMobilities;
        $viewingCampaign = $this->getViewingCampaignProperty();
        $campaignResults = $this->getCampaignResultsProperty();
    @endphp
    <style>
        .mc-feedback-grid{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(310px,.95fr);gap:1rem;align-items:start}.mc-feedback-list{display:grid;gap:.55rem}.mc-feedback-card{border:1px solid rgba(148,163,184,.22);border-radius:.85rem;padding:.8rem;background:rgba(255,255,255,.65)}.mc-feedback-actions{display:flex;gap:.38rem;flex-wrap:wrap;align-items:center}.mc-feedback-chip{display:inline-flex;align-items:center;padding:.18rem .42rem;border-radius:99px;background:#eef2ff;color:#4338ca;font-size:.61rem;font-weight:750}.mc-feedback-sub{color:#64748b;font-size:.68rem;line-height:1.45}.mc-feedback-question{padding:.7rem;border:1px solid rgba(148,163,184,.2);border-radius:.7rem;background:rgba(248,250,252,.74);margin-top:.5rem}.mc-feedback-fields{display:grid;grid-template-columns:1fr 1fr;gap:.55rem}.mc-feedback-field label{display:block;color:#64748b;font-size:.6rem;font-weight:800;letter-spacing:.04em;text-transform:uppercase;margin-bottom:.22rem}.mc-feedback-field input,.mc-feedback-field textarea,.mc-feedback-field select{width:100%;border:1px solid rgba(100,116,139,.28);border-radius:.5rem;padding:.47rem .55rem;background:transparent;font-size:.73rem}.mc-feedback-field textarea{min-height:70px;resize:vertical}.mc-feedback-modal{position:fixed;inset:0;z-index:50;display:flex;align-items:center;justify-content:center;padding:1rem;background:rgba(15,23,42,.45)}.mc-feedback-modal-panel{width:min(960px,100%);max-height:calc(100vh - 2rem);overflow:auto;border-radius:1rem;background:white;box-shadow:0 25px 70px rgba(15,23,42,.28);padding:1rem}.mc-feedback-empty{padding:1.4rem;text-align:center;color:#64748b;font-size:.75rem;border:1px dashed rgba(100,116,139,.3);border-radius:.8rem}@media(max-width:900px){.mc-feedback-grid{grid-template-columns:1fr}}@media(max-width:600px){.mc-feedback-fields{grid-template-columns:1fr}}
    </style>

    <div class="mc-feedback-grid">
        <div class="mc-feedback-list">
            <x-filament::section heading="Your feedback library" description="Create a form once. Its content is copied when you share it, so historical responses always keep their original questions.">
                <div style="display:flex;justify-content:space-between;gap:.6rem;align-items:center;flex-wrap:wrap;margin-bottom:.75rem;">
                    <div class="mc-feedback-sub">No personal details are requested or stored by the anonymous response flow.</div>
                    <div class="mc-feedback-actions">
                        <x-filament::button wire:click="openCreateForm(true)" size="sm" icon="heroicon-o-sparkles">Use evaluation starter</x-filament::button>
                        <x-filament::button wire:click="openCreateForm" color="gray" size="sm" icon="heroicon-o-plus">New blank form</x-filament::button>
                    </div>
                </div>
                @forelse($forms as $form)
                    <div class="mc-feedback-card" wire:key="feedback-form-{{ $form->id }}">
                        <div style="display:flex;justify-content:space-between;gap:.6rem;align-items:flex-start;">
                            <div>
                                <div style="font-size:.82rem;font-weight:780;">{{ $form->name }}</div>
                                <div class="mc-feedback-sub" style="margin-top:.16rem;">{{ $form->description ?: 'Reusable feedback form' }}</div>
                            </div>
                            <span class="mc-feedback-chip">{{ count($form->questions ?? []) }} questions</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;gap:.6rem;align-items:center;flex-wrap:wrap;margin-top:.62rem;">
                            <span class="mc-feedback-sub">Used in {{ $form->campaigns_count }} {{ \Illuminate\Support\Str::plural('mobility', $form->campaigns_count) }}</span>
                            <div class="mc-feedback-actions">
                                <x-filament::button wire:click="openShareForm({{ $form->id }})" size="sm" icon="heroicon-o-paper-airplane">Share with mobility</x-filament::button>
                                <x-filament::button wire:click="editForm({{ $form->id }})" color="gray" size="sm">Edit</x-filament::button>
                                <x-filament::button wire:click="duplicateForm({{ $form->id }})" color="gray" size="sm">Copy</x-filament::button>
                                <x-filament::button wire:click="archiveForm({{ $form->id }})" wire:confirm="Archive this form? Existing feedback links and results remain available." color="gray" size="sm">Archive</x-filament::button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="mc-feedback-empty">Start with the evaluation starter or build a completely blank form. You only need to create it once.</div>
                @endforelse
            </x-filament::section>
        </div>

        <x-filament::section heading="Shared feedback links" description="Each link belongs to one mobility. Responses are anonymous and never linked to participant records.">
            @forelse($campaigns as $campaign)
                @php($link = route('public.mobility-feedback.show', $campaign->public_token))
                <div class="mc-feedback-card" wire:key="feedback-campaign-{{ $campaign->id }}" style="margin-bottom:.55rem;">
                    <div style="display:flex;justify-content:space-between;gap:.6rem;align-items:flex-start;">
                        <div>
                            <div style="font-size:.78rem;font-weight:780;">{{ $campaign->title }}</div>
                            <div class="mc-feedback-sub" style="margin-top:.12rem;">{{ $campaign->mobility?->project?->name }} · {{ $campaign->mobility?->name }}</div>
                        </div>
                        <span class="mc-feedback-chip" style="background:{{ $campaign->hasActiveLink() ? '#ecfdf5' : '#f1f5f9' }};color:{{ $campaign->hasActiveLink() ? '#047857' : '#64748b' }};">{{ $campaign->hasActiveLink() ? 'Open' : 'Closed' }}</span>
                    </div>
                    <div class="mc-feedback-sub" style="margin-top:.42rem;">{{ $campaign->responses_count }} anonymous {{ \Illuminate\Support\Str::plural('response', $campaign->responses_count) }}</div>
                    <div class="mc-feedback-actions" style="margin-top:.58rem;">
                        <button type="button" x-data="{ copied:false }" x-on:click="navigator.clipboard && window.isSecureContext ? navigator.clipboard.writeText('{{ $link }}') : null; copied=true; setTimeout(() => copied=false, 1500)" style="padding:.34rem .48rem;border:1px solid rgba(100,116,139,.26);border-radius:.42rem;background:transparent;font-size:.65rem;font-weight:700;cursor:pointer;"><span x-text="copied ? 'Copied' : 'Copy link'">Copy link</span></button>
                        <a href="{{ $link }}" target="_blank" style="padding:.34rem .48rem;border:1px solid rgba(100,116,139,.26);border-radius:.42rem;color:inherit;font-size:.65rem;font-weight:700;text-decoration:none;">Open</a>
                        <x-filament::button wire:click="openResults({{ $campaign->id }})" color="gray" size="sm">Results</x-filament::button>
                        @if($campaign->canBeManagedBy(auth()->user()))
                            @if($campaign->hasActiveLink())
                                <x-filament::button wire:click="closeCampaign({{ $campaign->id }})" wire:confirm="Close this feedback link? It can be reopened later." color="gray" size="sm">Close</x-filament::button>
                            @else
                                <x-filament::button wire:click="reopenCampaign({{ $campaign->id }})" size="sm">Reopen</x-filament::button>
                            @endif
                        @endif
                    </div>
                </div>
            @empty
                <div class="mc-feedback-empty">No feedback link has been shared yet.</div>
            @endforelse
        </x-filament::section>
    </div>

    @if($showFormEditor)
        <div class="mc-feedback-modal" role="dialog" aria-modal="true">
            <div class="mc-feedback-modal-panel">
                <div style="display:flex;justify-content:space-between;gap:.8rem;align-items:flex-start;margin-bottom:.85rem;">
                    <div><h2 style="font-size:1rem;font-weight:800;margin:0;">{{ $editingFormId ? 'Edit feedback form' : 'Create feedback form' }}</h2><p class="mc-feedback-sub" style="margin:.2rem 0 0;">Build the survey once; each shared link keeps a fixed snapshot of it.</p></div>
                    <button wire:click="closeModal('editor')" type="button" style="border:0;background:transparent;font-size:1.2rem;cursor:pointer;">×</button>
                </div>
                <div class="mc-feedback-fields">
                    <div class="mc-feedback-field"><label>Form name</label><input wire:model="formName" placeholder="e.g. Final mobility evaluation">@error('formName')<div style="color:#dc2626;font-size:.63rem;">{{ $message }}</div>@enderror</div>
                    <div class="mc-feedback-field"><label>Short description</label><input wire:model="formDescription" placeholder="Shown only in your library"></div>
                    <div class="mc-feedback-field" style="grid-column:1/-1;"><label>Welcome text</label><textarea wire:model="formIntro" placeholder="Explain why feedback is collected and that it is anonymous."></textarea></div>
                    <div class="mc-feedback-field" style="grid-column:1/-1;"><label>Thank-you message</label><textarea wire:model="formThankYou"></textarea></div>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;gap:.6rem;margin:1rem 0 .3rem;"><div><div style="font-size:.82rem;font-weight:780;">Questions</div><div class="mc-feedback-sub">Use only questions that do not identify the person.</div></div><div class="mc-feedback-actions">@foreach(\App\Models\FeedbackForm::QUESTION_TYPES as $type => $label)<x-filament::button wire:click="addQuestion('{{ $type }}')" color="gray" size="sm">+ {{ $label }}</x-filament::button>@endforeach</div></div>
                @error('formQuestions')<div style="color:#dc2626;font-size:.65rem;">{{ $message }}</div>@enderror
                @foreach($formQuestions as $index => $question)
                    <div class="mc-feedback-question" wire:key="feedback-question-{{ $question['id'] }}">
                        <div style="display:flex;justify-content:space-between;gap:.5rem;align-items:center;margin-bottom:.45rem;"><span class="mc-feedback-chip">Question {{ $index + 1 }}</span><div class="mc-feedback-actions"><button type="button" wire:click="moveQuestion({{ $index }}, 'up')" style="border:0;background:transparent;cursor:pointer;">↑</button><button type="button" wire:click="moveQuestion({{ $index }}, 'down')" style="border:0;background:transparent;cursor:pointer;">↓</button><button type="button" wire:click="removeQuestion({{ $index }})" style="border:0;background:transparent;color:#dc2626;cursor:pointer;">Remove</button></div></div>
                        <div class="mc-feedback-fields">
                            <div class="mc-feedback-field"><label>Type</label><select wire:model.live="formQuestions.{{ $index }}.type">@foreach(\App\Models\FeedbackForm::QUESTION_TYPES as $type => $label)<option value="{{ $type }}">{{ $label }}</option>@endforeach</select></div>
                            <div class="mc-feedback-field"><label>Required</label><label style="display:flex;gap:.35rem;align-items:center;padding-top:.42rem;font-size:.72rem;font-weight:650;text-transform:none;letter-spacing:0;color:inherit;"><input type="checkbox" wire:model="formQuestions.{{ $index }}.required"> Required response</label></div>
                            <div class="mc-feedback-field" style="grid-column:1/-1;"><label>Question</label><input wire:model="formQuestions.{{ $index }}.label" placeholder="Write the question participants will see">@error('formQuestions.'.$index.'.label')<div style="color:#dc2626;font-size:.63rem;">{{ $message }}</div>@enderror</div>
                            <div class="mc-feedback-field" style="grid-column:1/-1;"><label>Helpful context (optional)</label><input wire:model="formQuestions.{{ $index }}.help" placeholder="A short explanation below the question"></div>
                            @if(in_array($question['type'] ?? '', ['single_choice', 'multiple_choice'], true))
                                <div class="mc-feedback-field" style="grid-column:1/-1;"><label>Choices — one per line</label><textarea wire:model="formQuestions.{{ $index }}.options_text" placeholder="Option 1&#10;Option 2"></textarea><div class="mc-feedback-sub">For now, keep each option on its own line.</div>@error('formQuestions.'.$index.'.options')<div style="color:#dc2626;font-size:.63rem;">{{ $message }}</div>@enderror</div>
                            @endif
                        </div>
                    </div>
                @endforeach
                <div style="display:flex;justify-content:flex-end;gap:.5rem;margin-top:1rem;"><x-filament::button wire:click="closeModal('editor')" color="gray">Cancel</x-filament::button><x-filament::button wire:click="saveForm" wire:loading.attr="disabled" wire:target="saveForm">Save form</x-filament::button></div>
            </div>
        </div>
    @endif

    @if($showShareModal)
        <div class="mc-feedback-modal" role="dialog" aria-modal="true"><div class="mc-feedback-modal-panel" style="width:min(580px,100%);"><div style="display:flex;justify-content:space-between;gap:.8rem;align-items:flex-start;margin-bottom:.85rem;"><div><h2 style="font-size:1rem;font-weight:800;margin:0;">Share anonymous feedback</h2><p class="mc-feedback-sub" style="margin:.2rem 0 0;">The selected form will be copied for this mobility. Later form edits will not change it.</p></div><button wire:click="closeModal('share')" type="button" style="border:0;background:transparent;font-size:1.2rem;cursor:pointer;">×</button></div><div class="mc-feedback-fields"><div class="mc-feedback-field"><label>Mobility</label><select wire:model="shareMobilityId"><option value="">Choose a mobility</option>@foreach($mobilities as $mobility)<option value="{{ $mobility->id }}">{{ $mobility->project?->name }} · {{ $mobility->name }}</option>@endforeach</select>@error('shareMobilityId')<div style="color:#dc2626;font-size:.63rem;">{{ $message }}</div>@enderror</div><div class="mc-feedback-field"><label>Link title</label><input wire:model="shareCampaignTitle">@error('shareCampaignTitle')<div style="color:#dc2626;font-size:.63rem;">{{ $message }}</div>@enderror</div></div><div style="padding:.65rem;border:1px solid rgba(34,197,94,.23);border-radius:.65rem;background:#f0fdf4;color:#166534;font-size:.69rem;line-height:1.45;margin-top:.8rem;">This creates one public link. The platform does not collect names, emails, participant IDs, IP addresses or user accounts with responses.</div><div style="display:flex;justify-content:flex-end;gap:.5rem;margin-top:1rem;"><x-filament::button wire:click="closeModal('share')" color="gray">Cancel</x-filament::button><x-filament::button wire:click="createCampaign" wire:loading.attr="disabled" wire:target="createCampaign">Create link</x-filament::button></div></div></div>
    @endif

    @if($showResultsModal && $viewingCampaign)
        @php($feedbackLink = route('public.mobility-feedback.show', $viewingCampaign->public_token))
        <div class="mc-feedback-modal" role="dialog" aria-modal="true">
            <div class="mc-feedback-modal-panel">
                <div style="display:flex;justify-content:space-between;gap:.8rem;align-items:flex-start;margin-bottom:.85rem;">
                    <div>
                        <h2 style="font-size:1rem;font-weight:800;margin:0;">{{ $viewingCampaign->title }}</h2>
                        <p class="mc-feedback-sub" style="margin:.2rem 0 0;">{{ $viewingCampaign->mobility?->project?->name }} · {{ $viewingCampaign->mobility?->name }}</p>
                    </div>
                    <button wire:click="closeModal('results')" type="button" aria-label="Close results" style="border:0;background:transparent;font-size:1.2rem;cursor:pointer;">×</button>
                </div>
                <div style="display:flex;gap:.4rem;align-items:center;flex-wrap:wrap;padding:.62rem;border:1px solid rgba(99,102,241,.18);border-radius:.65rem;background:#f8faff;margin-bottom:.85rem;">
                    <input readonly value="{{ $feedbackLink }}" style="flex:1;min-width:220px;border:0;background:transparent;font-size:.7rem;outline:0;">
                    <button type="button" x-data="{ copied:false }" x-on:click="navigator.clipboard && window.isSecureContext ? navigator.clipboard.writeText('{{ $feedbackLink }}') : null; copied=true; setTimeout(() => copied=false, 1500)" style="padding:.36rem .5rem;border:1px solid rgba(100,116,139,.25);border-radius:.45rem;background:white;cursor:pointer;font-size:.66rem;font-weight:700;"><span x-text="copied ? 'Copied' : 'Copy link'">Copy link</span></button>
                    <a href="{{ $feedbackLink }}" target="_blank" style="padding:.36rem .5rem;border:1px solid rgba(100,116,139,.25);border-radius:.45rem;background:white;color:inherit;text-decoration:none;font-size:.66rem;font-weight:700;">Open</a>
                    <a href="{{ route('feedback-campaigns.export-pdf', $viewingCampaign) }}" style="padding:.36rem .5rem;border:1px solid rgba(100,116,139,.25);border-radius:.45rem;color:inherit;text-decoration:none;font-size:.66rem;font-weight:700;">Export PDF</a>
                    <a href="{{ route('feedback-campaigns.export', $viewingCampaign) }}" style="padding:.36rem .5rem;border:1px solid rgba(100,116,139,.25);border-radius:.45rem;color:inherit;text-decoration:none;font-size:.66rem;font-weight:700;">Export CSV</a>
                </div>
                @include('filament.pages.partials.mobility-feedback-analytics', ['analytics' => $campaignResults])
            </div>
        </div>
    @endif
</x-filament-panels::page>
