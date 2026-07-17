<x-filament-panels::page>
    <x-ui-polish />

    <style>
        .mc-billing{display:grid;gap:1rem}.mc-billing-cards{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:.8rem}.mc-billing-card{display:grid;gap:.5rem;border:1px solid rgba(148,163,184,.18);border-radius:1rem;padding:1rem;text-decoration:none;background:rgba(255,255,255,.02);min-height:8.5rem}.mc-billing-card:hover{background:rgba(99,102,241,.06)}.mc-billing-card-top{display:flex;align-items:flex-start;justify-content:space-between;gap:.75rem}.mc-billing-count{font-size:1.75rem;font-weight:900;line-height:1;color:#111827}.dark .mc-billing-count{color:#fff}.mc-billing-label{font-size:.78rem;font-weight:850;color:#111827}.dark .mc-billing-label{color:#fff}.mc-billing-amount{font-size:.95rem;font-weight:850}.mc-billing-detail{font-size:.72rem;line-height:1.45;color:#64748b}.mc-billing-pill{border-radius:999px;padding:.18rem .5rem;font-size:.62rem;font-weight:850;text-transform:uppercase;letter-spacing:.04em}.mc-billing-warning{background:rgba(245,158,11,.12);color:#b45309}.mc-billing-danger{background:rgba(239,68,68,.12);color:#dc2626}.mc-billing-info{background:rgba(59,130,246,.12);color:#2563eb}.mc-billing-success{background:rgba(16,185,129,.12);color:#047857}.mc-billing-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}.mc-billing-list{display:grid;gap:.65rem}.mc-billing-row{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;border:1px solid rgba(148,163,184,.16);border-radius:.85rem;padding:.8rem;text-decoration:none}.mc-billing-row:hover{background:rgba(99,102,241,.05)}.mc-billing-title{font-size:.82rem;font-weight:850;color:#111827}.dark .mc-billing-title{color:#fff}.mc-billing-meta{font-size:.7rem;color:#64748b;line-height:1.45;margin-top:.15rem}.mc-billing-price{text-align:right;font-weight:900;color:#111827;white-space:nowrap}.dark .mc-billing-price{color:#fff}.mc-billing-empty{border:1px dashed rgba(148,163,184,.28);border-radius:.85rem;padding:.9rem;color:#64748b;font-size:.78rem;line-height:1.5}.mc-billing-actions{display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.85rem}.mc-billing-button{display:inline-flex;align-items:center;border-radius:.7rem;border:1px solid rgba(99,102,241,.22);padding:.48rem .7rem;text-decoration:none;font-size:.72rem;font-weight:800}.mc-billing-button:hover{background:rgba(99,102,241,.08)}@media(max-width:1200px){.mc-billing-cards{grid-template-columns:repeat(2,minmax(0,1fr))}.mc-billing-grid{grid-template-columns:1fr}}@media(max-width:700px){.mc-billing-cards{grid-template-columns:1fr}.mc-billing-row{display:grid}.mc-billing-price{text-align:left}}
    </style>

    <div class="mc-billing">
        <div class="mc-billing-cards">
            @foreach($this->cards() as $card)
                <a href="{{ $card['url'] }}" class="mc-billing-card">
                    <div class="mc-billing-card-top">
                        <div>
                            <div class="mc-billing-label">{{ $card['label'] }}</div>
                            <div class="mc-billing-count" style="margin-top:.45rem;">{{ $card['count'] }}</div>
                        </div>
                        <span class="mc-billing-pill mc-billing-{{ $card['level'] }}">{{ $card['level'] }}</span>
                    </div>
                    <div class="mc-billing-amount">{{ $card['amount'] }}</div>
                    <div class="mc-billing-detail">{{ $card['detail'] }}</div>
                </a>
            @endforeach
        </div>

        <div class="mc-billing-grid">
            <x-filament::section heading="Ready to invoice" description="Projects with complete billing data and a pending external fiscal invoice.">
                <div class="mc-billing-list">
                    @forelse($this->todayQueue() as $project)
                        <a href="{{ $this->paymentQueueUrl('to_invoice') }}" class="mc-billing-row">
                            <div>
                                <div class="mc-billing-title">{{ $project->name }}</div>
                                <div class="mc-billing-meta">
                                    {{ $project->ownerAccount?->billing_name ?: $project->ownerAccount?->email }}
                                    @if($project->invoice_due_at)
                                        · target by {{ $project->invoice_due_at->format('d M Y') }}
                                    @endif
                                </div>
                            </div>
                            <div class="mc-billing-price">{{ $this->formatEuro($project->activation_fee_amount) }}</div>
                        </a>
                    @empty
                        <div class="mc-billing-empty">No projects are ready for fiscal invoicing right now.</div>
                    @endforelse
                </div>
                <div class="mc-billing-actions">
                    <a class="mc-billing-button" href="{{ $this->paymentQueueUrl('to_invoice') }}">Open invoicing queue</a>
                </div>
            </x-filament::section>

            <x-filament::section heading="Overdue payments" description="Projects where payment follow-up may be needed.">
                <div class="mc-billing-list">
                    @forelse($this->overdueQueue() as $project)
                        <a href="{{ $this->paymentQueueUrl('overdue') }}" class="mc-billing-row">
                            <div>
                                <div class="mc-billing-title">{{ $project->name }}</div>
                                <div class="mc-billing-meta">
                                    {{ $project->ownerAccount?->email ?: 'No owner' }}
                                    @if($project->invoice_due_at)
                                        · due {{ $project->invoice_due_at->format('d M Y') }}
                                    @endif
                                </div>
                            </div>
                            <div class="mc-billing-price">{{ $this->formatEuro($project->activation_fee_amount) }}</div>
                        </a>
                    @empty
                        <div class="mc-billing-empty">No overdue project payments. Beautiful little silence.</div>
                    @endforelse
                </div>
                <div class="mc-billing-actions">
                    <a class="mc-billing-button" href="{{ $this->paymentQueueUrl('overdue') }}">Open overdue queue</a>
                </div>
            </x-filament::section>

            <x-filament::section heading="Invoice sent, awaiting payment" description="External invoices already sent; mark paid when payment is confirmed.">
                <div class="mc-billing-list">
                    @forelse($this->sentQueue() as $project)
                        <a href="{{ $this->paymentQueueUrl('sent') }}" class="mc-billing-row">
                            <div>
                                <div class="mc-billing-title">{{ $project->name }}</div>
                                <div class="mc-billing-meta">
                                    {{ $project->invoice_number ? 'Invoice '.$project->invoice_number : 'No invoice number stored' }}
                                    @if($project->invoice_due_at)
                                        · due {{ $project->invoice_due_at->format('d M Y') }}
                                    @endif
                                </div>
                            </div>
                            <div class="mc-billing-price">{{ $this->formatEuro($project->activation_fee_amount) }}</div>
                        </a>
                    @empty
                        <div class="mc-billing-empty">No sent invoices waiting for payment.</div>
                    @endforelse
                </div>
                <div class="mc-billing-actions">
                    <a class="mc-billing-button" href="{{ $this->paymentQueueUrl('sent') }}">Open sent invoices</a>
                </div>
            </x-filament::section>

            <x-filament::section heading="Missing billing details" description="Accounts that block invoicing because required billing fields are incomplete.">
                <div class="mc-billing-list">
                    @forelse($this->missingBillingQueue() as $project)
                        <a href="{{ $this->accountUrl($project) ?: $this->paymentQueueUrl('missing_billing') }}" class="mc-billing-row">
                            <div>
                                <div class="mc-billing-title">{{ $project->ownerAccount?->email ?: 'No owner' }}</div>
                                <div class="mc-billing-meta">{{ $project->name }} · ask user to complete billing name, country and address.</div>
                            </div>
                            <div class="mc-billing-price">Fix</div>
                        </a>
                    @empty
                        <div class="mc-billing-empty">All billable project owners have complete billing details.</div>
                    @endforelse
                </div>
                <div class="mc-billing-actions">
                    <a class="mc-billing-button" href="{{ $this->paymentQueueUrl('missing_billing') }}">Open missing billing</a>
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
