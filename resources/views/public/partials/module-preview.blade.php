@php($type = $type ?? 'writing')

<article class="mc-preview" aria-label="{{ ucfirst($type) }} module preview">
    @if($type === 'writing')
        <div class="mc-preview-top">
            <strong class="mc-preview-title">Application workspace</strong>
            <span class="mc-preview-chip">Readiness 92</span>
        </div>
        <div class="mc-lines">
            <div class="mc-line" style="--w:84%"></div>
            <div class="mc-line" style="--w:62%"></div>
            <div class="mc-line" style="--w:76%"></div>
            <div class="mc-line" style="--w:48%"></div>
        </div>
        <div class="mc-screen-card" style="margin-top:.85rem;">
            <p class="mc-card-label">Selected question</p>
            <p style="margin-top:.45rem;color:#475569;line-height:1.5;font-size:.85rem;">Describe your project objectives and how the activities answer the needs of the target group.</p>
            <div class="mc-bar"><span style="width:73%"></span></div>
        </div>
    @elseif($type === 'budget')
        <div class="mc-preview-top">
            <strong class="mc-preview-title">Budget control</strong>
            <span class="mc-preview-chip">€35,000 left</span>
        </div>
        <div class="mc-table">
            <div class="mc-table-row"><span></span><span></span><span></span></div>
            <div class="mc-table-row"><span></span><span></span><span></span></div>
            <div class="mc-table-row"><span></span><span></span><span></span></div>
        </div>
        <div class="mc-screen-card" style="margin-top:.85rem;">
            <p class="mc-card-label">Travel</p>
            <p class="mc-card-value">€8,000</p>
            <div class="mc-bar"><span style="width:44%"></span></div>
        </div>
    @else
        <div class="mc-preview-top">
            <strong class="mc-preview-title">Evidence by day</strong>
            <span class="mc-preview-chip">Mobility</span>
        </div>
        <div class="mc-gallery">
            <div class="mc-photo"></div>
            <div class="mc-photo"></div>
            <div class="mc-photo"></div>
            <div class="mc-photo"></div>
        </div>
        <div class="mc-screen-card" style="margin-top:.85rem;">
            <p class="mc-card-label">Day 3 · Workshop and local visit</p>
            <p style="margin-top:.45rem;color:#475569;line-height:1.5;font-size:.85rem;">Photos, files, links and notes are grouped by activity day for easier reporting.</p>
        </div>
    @endif
</article>
