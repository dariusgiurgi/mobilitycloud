@php($type = $type ?? 'writing')

<article class="mc-preview" aria-label="{{ ucfirst($type) }} module preview">
    @if($type === 'writing')
        <div class="mc-preview-top">
            <strong class="mc-preview-title">Application workspace</strong>
            <span class="mc-preview-chip mc-chip-success">82% complete</span>
        </div>
        <div class="mc-preview-project">
            <div class="mc-project-mark">KA</div>
            <div>
                <strong>Green Skills Exchange</strong>
                <span>KA122-VET · 2026 call</span>
            </div>
            <span class="mc-stage-chip">Draft</span>
        </div>
        <div class="mc-writing-layout">
            <div class="mc-writing-nav" aria-hidden="true">
                <span class="is-done"><i>✓</i>Context</span>
                <span class="is-done"><i>✓</i>Objectives</span>
                <span class="is-active"><i>3</i>Activities</span>
                <span><i>4</i>Impact</span>
            </div>
            <div class="mc-writing-editor">
                <div class="mc-editor-head">
                    <div><b>Activities and methodology</b><span>2,184 / 3,000 characters</span></div>
                    <span class="mc-saved">Saved</span>
                </div>
                <p>Three staff mobility activities will strengthen digital teaching practice and create reusable learning resources for our partner schools.</p>
                <div class="mc-editor-lines"><i></i><i></i><i></i></div>
                <div class="mc-bar"><span style="width:73%"></span></div>
            </div>
        </div>
    @elseif($type === 'budget')
        <div class="mc-preview-top">
            <strong class="mc-preview-title">Budget control</strong>
            <span class="mc-preview-chip">€35,000 available</span>
        </div>
        <div class="mc-budget-summary">
            <div><span>Approved grant</span><strong>€43,000</strong></div>
            <div><span>Spent to date</span><strong>€8,000</strong></div>
            <div><span>Evidence ready</span><strong>14 / 18</strong></div>
        </div>
        <div class="mc-budget-table">
            <div class="mc-budget-row mc-budget-head"><span>Basket</span><span>Allocated</span><span>Spent</span><span>Proof</span></div>
            <div class="mc-budget-row"><span><i class="mc-basket-dot travel"></i>Travel</span><strong>€12,000</strong><strong>€5,260</strong><em>6 / 8</em></div>
            <div class="mc-budget-row"><span><i class="mc-basket-dot support"></i>Individual support</span><strong>€19,500</strong><strong>€1,840</strong><em>4 / 4</em></div>
            <div class="mc-budget-row"><span><i class="mc-basket-dot course"></i>Course fees</span><strong>€6,500</strong><strong>€900</strong><em>2 / 3</em></div>
        </div>
        <div class="mc-spend-note">
            <span>18.6% of grant used</span>
            <div class="mc-bar"><span style="width:18.6%"></span></div>
        </div>
    @else
        <div class="mc-preview-top">
            <strong class="mc-preview-title">Evidence by day</strong>
            <span class="mc-preview-chip">Lisbon · 5 days</span>
        </div>
        <div class="mc-mobility-meta">
            <span>12–16 May 2026</span><b>16 participants</b><span class="mc-saved">All synced</span>
        </div>
        <div class="mc-evidence-list">
            <div class="mc-evidence-day is-complete">
                <span class="mc-day-number">01</span><div><b>Arrival and welcome</b><small>12 May · 8 photos · 2 files</small></div><i>✓</i>
            </div>
            <div class="mc-evidence-day is-complete">
                <span class="mc-day-number">02</span><div><b>Digital workshop</b><small>13 May · 12 photos · agenda</small></div><i>✓</i>
            </div>
            <div class="mc-evidence-day is-active">
                <span class="mc-day-number">03</span><div><b>School visit and reflection</b><small>14 May · 6 photos · 1 note pending</small></div><i>+</i>
            </div>
        </div>
        <div class="mc-evidence-footer">
            <div class="mc-mini-stack"><i></i><i></i><i></i></div>
            <span><b>26 files</b> ready for final archive</span>
        </div>
    @endif
</article>
