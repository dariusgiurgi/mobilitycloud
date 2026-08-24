<x-filament-panels::page>
    <x-ui-polish />
    @php
        $forms = $this->getForms();
        $mobilities = $this->getMobilities();
        $campaigns = $this->getCampaigns();
        $viewingCampaign = $this->getViewingCampaign();
        $analytics = $this->getViewingCampaignAnalytics();
        $canManage = $this->canManageFeedback();
        $campaignsByMobility = $campaigns->groupBy('project_mobility_id');
    @endphp
    <style>
        .mc-project-feedback-hero{padding:1rem 1.1rem;border:1px solid rgba(99,102,241,.2);border-radius:1rem;background:linear-gradient(120deg,rgba(99,102,241,.1),rgba(14,165,233,.04))}.mc-project-feedback-grid{display:grid;grid-template-columns:minmax(230px,.7fr) minmax(0,1.3fr);gap:1rem;align-items:start;margin-top:1rem}.mc-project-feedback-library,.mc-project-feedback-mobility{padding:.85rem;border:1px solid rgba(148,163,184,.2);border-radius:.9rem;background:#fff}.mc-project-feedback-mobility{margin-bottom:.65rem}.mc-project-feedback-modal{position:fixed;inset:0;z-index:60;display:flex;align-items:center;justify-content:center;padding:1rem;background:rgba(15,23,42,.45)}.mc-project-feedback-modal-panel{width:min(960px,100%);max-height:calc(100vh - 2rem);overflow:auto;border-radius:1rem;background:white;box-shadow:0 25px 70px rgba(15,23,42,.28);padding:1rem}.mc-project-feedback-sub{color:#64748b;font-size:.68rem;line-height:1.45}.mc-project-feedback-form{padding:.65rem 0;border-bottom:1px solid rgba(148,163,184,.16)}.mc-project-feedback-form:last-child{border-bottom:0}.mc-project-feedback-link{display:flex;justify-content:space-between;gap:.8rem;align-items:center;flex-wrap:wrap;padding:.7rem 0;border-top:1px solid rgba(148,163,184,.16)}.mc-project-feedback-link:first-of-type{border-top:0}@media(max-width:900px){.mc-project-feedback-grid{grid-template-columns:1fr}}
    </style>

    <section class="mc-project-feedback-hero">
        <div style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <div style="font-size:.62rem;font-weight:850;letter-spacing:.08em;text-transform:uppercase;color:#6366f1;">Project feedback</div>
                <h2 class="text-gray-950 dark:text-white" style="font-size:1.05rem;font-weight:850;margin:.18rem 0 0;">Anonymous evaluations, organised by mobility</h2>
                <p class="mc-project-feedback-sub" style="margin:.24rem 0 0;max-width:650px;">Choose a reusable form, create a separate link for each mobility and review anonymous results here. Responses are never connected to participant records.</p>
            </div>
            @if($canManage)
                <x-filament::button tag="a" :href="$this->formLibraryUrl()" color="gray" size="sm" icon="heroicon-m-squares-2x2">Manage form library</x-filament::button>
            @endif
        </div>
    </section>

    <div class="mc-project-feedback-grid">
        <aside class="mc-project-feedback-library">
            <div style="display:flex;justify-content:space-between;gap:.6rem;align-items:start;margin-bottom:.35rem;"><div><h3 class="text-gray-950 dark:text-white" style="font-size:.82rem;font-weight:800;margin:0;">Form library</h3><p class="mc-project-feedback-sub" style="margin:.14rem 0 0;">Reusable forms from your account.</p></div><span style="font-size:.62rem;font-weight:750;color:#64748b;">{{ $forms->count() }}</span></div>
            @forelse($forms as $form)
                <div class="mc-project-feedback-form" wire:key="project-feedback-form-{{ $form->id }}">
                    <div class="text-gray-950 dark:text-white" style="font-size:.74rem;font-weight:780;">{{ $form->name }}</div>
                    <div class="mc-project-feedback-sub" style="margin-top:.12rem;">{{ count($form->questions ?? []) }} questions · used in {{ $form->campaigns_count }} {{ \Illuminate\Support\Str::plural('mobility', $form->campaigns_count) }}</div>
                    @if($canManage)
                        <x-filament::button wire:click="openShareForm({{ $form->id }})" size="sm" color="gray" icon="heroicon-m-paper-airplane" style="margin-top:.48rem;">Create link</x-filament::button>
                    @endif
                </div>
            @empty
                <div class="mc-project-feedback-sub" style="padding:.65rem 0;">Create a reusable form in the library first, then return here to share it with a mobility.</div>
                @if($canManage)<x-filament::button tag="a" :href="$this->formLibraryUrl()" size="sm">Open form library</x-filament::button>@endif
            @endforelse
        </aside>

        <section>
            <div style="display:flex;justify-content:space-between;gap:.6rem;align-items:center;margin:0 0 .5rem .15rem;"><div><h3 class="text-gray-950 dark:text-white" style="font-size:.82rem;font-weight:800;margin:0;">Feedback links in this project</h3><p class="mc-project-feedback-sub" style="margin:.12rem 0 0;">Every link belongs to one mobility.</p></div><span class="mc-project-feedback-sub">{{ $campaigns->count() }} total</span></div>
            @forelse($mobilities as $mobility)
                @php($mobilityCampaigns = $campaignsByMobility->get($mobility->id, collect()))
                <article class="mc-project-feedback-mobility" wire:key="project-feedback-mobility-{{ $mobility->id }}">
                    <div style="display:flex;justify-content:space-between;gap:.7rem;align-items:start;"><div><h3 class="text-gray-950 dark:text-white" style="font-size:.8rem;font-weight:800;margin:0;">{{ $mobility->name }}</h3><p class="mc-project-feedback-sub" style="margin:.13rem 0 0;">{{ $mobility->start_date?->format('d M Y') ?: 'Date not set' }}{{ $mobility->end_date ? ' – '.$mobility->end_date->format('d M Y') : '' }}</p></div><span class="mc-project-feedback-sub">{{ $mobilityCampaigns->count() }} {{ \Illuminate\Support\Str::plural('form', $mobilityCampaigns->count()) }}</span></div>
                    @forelse($mobilityCampaigns as $campaign)
                        @php($link = route('public.mobility-feedback.show', $campaign->public_token))
                        <div class="mc-project-feedback-link" wire:key="project-feedback-campaign-{{ $campaign->id }}">
                            <div><div class="text-gray-950 dark:text-white" style="font-size:.74rem;font-weight:780;">{{ $campaign->title }}</div><div class="mc-project-feedback-sub" style="margin-top:.12rem;">{{ $campaign->responses_count }} anonymous {{ \Illuminate\Support\Str::plural('response', $campaign->responses_count) }} · {{ $campaign->hasActiveLink() ? 'Link open' : 'Link closed' }}</div></div>
                            <div style="display:flex;gap:.38rem;flex-wrap:wrap;align-items:center;"><button type="button" x-data="{ copied:false }" x-on:click="navigator.clipboard && window.isSecureContext ? navigator.clipboard.writeText('{{ $link }}') : null; copied=true; setTimeout(() => copied=false, 1500)" style="padding:.34rem .48rem;border:1px solid rgba(100,116,139,.26);border-radius:.42rem;background:transparent;font-size:.65rem;font-weight:700;cursor:pointer;"><span x-text="copied ? 'Copied' : 'Copy link'">Copy link</span></button><a href="{{ $link }}" target="_blank" style="padding:.34rem .48rem;border:1px solid rgba(100,116,139,.26);border-radius:.42rem;color:inherit;font-size:.65rem;font-weight:700;text-decoration:none;">Open</a><x-filament::button wire:click="openResults({{ $campaign->id }})" color="gray" size="sm">Results</x-filament::button>@if($canManage)@if($campaign->hasActiveLink())<x-filament::button wire:click="closeCampaign({{ $campaign->id }})" wire:confirm="Close this feedback link? It can be reopened later." color="gray" size="sm">Close</x-filament::button>@else<x-filament::button wire:click="reopenCampaign({{ $campaign->id }})" size="sm">Reopen</x-filament::button>@endif@endif</div>
                        </div>
                    @empty
                        <p class="mc-project-feedback-sub" style="margin:.7rem 0 0;">No feedback link for this mobility yet.</p>
                    @endforelse
                </article>
            @empty
                <div class="mc-project-feedback-mobility"><p class="mc-project-feedback-sub" style="margin:0;">Add a mobility before creating a feedback link.</p></div>
            @endforelse
        </section>
    </div>

    @if($showShareModal)
        <div class="mc-project-feedback-modal" role="dialog" aria-modal="true"><div class="mc-project-feedback-modal-panel" style="width:min(580px,100%);"><div style="display:flex;justify-content:space-between;gap:.8rem;align-items:flex-start;margin-bottom:.85rem;"><div><h2 style="font-size:1rem;font-weight:800;margin:0;">Create anonymous feedback link</h2><p class="mc-project-feedback-sub" style="margin:.2rem 0 0;">The selected form is copied for one mobility. Later edits in the library do not alter this link.</p></div><button wire:click="closeModal('share')" type="button" aria-label="Close" style="border:0;background:transparent;font-size:1.2rem;cursor:pointer;">×</button></div><div style="display:grid;grid-template-columns:1fr 1fr;gap:.6rem;"><div><label class="mc-project-feedback-sub" style="display:block;font-weight:800;margin-bottom:.2rem;">Mobility</label><select wire:model="shareMobilityId" style="width:100%;padding:.5rem .58rem;border:1px solid rgba(100,116,139,.28);border-radius:.55rem;background:transparent;font-size:.74rem;"><option value="">Choose a mobility</option>@foreach($mobilities as $mobility)<option value="{{ $mobility->id }}">{{ $mobility->name }}</option>@endforeach</select>@error('shareMobilityId')<div style="color:#dc2626;font-size:.63rem;">{{ $message }}</div>@enderror</div><div><label class="mc-project-feedback-sub" style="display:block;font-weight:800;margin-bottom:.2rem;">Link title</label><input wire:model="shareCampaignTitle" style="width:100%;padding:.5rem .58rem;border:1px solid rgba(100,116,139,.28);border-radius:.55rem;background:transparent;font-size:.74rem;">@error('shareCampaignTitle')<div style="color:#dc2626;font-size:.63rem;">{{ $message }}</div>@enderror</div></div><div style="padding:.65rem;border:1px solid rgba(34,197,94,.23);border-radius:.65rem;background:#f0fdf4;color:#166534;font-size:.69rem;line-height:1.45;margin-top:.8rem;">This creates one public link. The platform does not collect names, emails, participant IDs, IP addresses or accounts with responses.</div><div style="display:flex;justify-content:flex-end;gap:.5rem;margin-top:1rem;"><x-filament::button wire:click="closeModal('share')" color="gray">Cancel</x-filament::button><x-filament::button wire:click="createCampaign" wire:loading.attr="disabled" wire:target="createCampaign">Create link</x-filament::button></div></div></div>
    @endif

    @if($showResultsModal && $viewingCampaign)
        @php($feedbackLink = route('public.mobility-feedback.show', $viewingCampaign->public_token))
        <div class="mc-project-feedback-modal" role="dialog" aria-modal="true"><div class="mc-project-feedback-modal-panel"><div style="display:flex;justify-content:space-between;gap:.8rem;align-items:flex-start;margin-bottom:.85rem;"><div><h2 style="font-size:1rem;font-weight:800;margin:0;">{{ $viewingCampaign->title }}</h2><p class="mc-project-feedback-sub" style="margin:.2rem 0 0;">{{ $record->name }} · {{ $viewingCampaign->mobility?->name }}</p></div><button wire:click="closeModal('results')" type="button" aria-label="Close" style="border:0;background:transparent;font-size:1.2rem;cursor:pointer;">×</button></div><div style="display:flex;gap:.4rem;align-items:center;flex-wrap:wrap;padding:.62rem;border:1px solid rgba(99,102,241,.18);border-radius:.65rem;background:#f8faff;margin-bottom:.85rem;"><input readonly value="{{ $feedbackLink }}" style="flex:1;min-width:220px;border:0;background:transparent;font-size:.7rem;outline:0;"><button type="button" x-data="{ copied:false }" x-on:click="navigator.clipboard && window.isSecureContext ? navigator.clipboard.writeText('{{ $feedbackLink }}') : null; copied=true; setTimeout(() => copied=false, 1500)" style="padding:.36rem .5rem;border:1px solid rgba(100,116,139,.25);border-radius:.45rem;background:white;cursor:pointer;font-size:.66rem;font-weight:700;"><span x-text="copied ? 'Copied' : 'Copy link'">Copy link</span></button><a href="{{ route('feedback-campaigns.export-pdf', $viewingCampaign) }}" style="padding:.36rem .5rem;border:1px solid rgba(100,116,139,.25);border-radius:.45rem;color:inherit;text-decoration:none;font-size:.66rem;font-weight:700;">Export PDF</a><a href="{{ route('feedback-campaigns.export', $viewingCampaign) }}" style="padding:.36rem .5rem;border:1px solid rgba(100,116,139,.25);border-radius:.45rem;color:inherit;text-decoration:none;font-size:.66rem;font-weight:700;">Export CSV</a></div>@include('filament.pages.partials.mobility-feedback-analytics', ['analytics' => $analytics])</div></div>
    @endif
</x-filament-panels::page>
