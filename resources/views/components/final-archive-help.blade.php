@props(['help', 'canConfigure', 'answers'])
<style>
    .mc-export-help { border:1px solid #c7d2fe;border-radius:1rem;background:#f8faff;color:#334155;min-width:0;scroll-margin-top:5rem; }
    .mc-export-help > summary { display:flex;align-items:center;gap:.8rem;cursor:pointer;padding:1rem;list-style:none; }
    .mc-export-help > summary::-webkit-details-marker { display:none; }
    .mc-export-help > summary img { width:48px;height:48px;flex:none;object-fit:contain; }
    .mc-export-help > summary strong { display:block;color:#312e81;font-size:.9375rem; }
    .mc-export-help > summary small { display:block;color:#526175;font-size:.8125rem;margin-top:.2rem; }
    .mc-export-help-chevron { margin-left:auto;font-size:1.3rem;color:#6366f1; }
    .mc-export-help[open] .mc-export-help-chevron { transform:rotate(90deg); }
    .mc-export-help-body { padding:0 1rem 1rem;font-size:.875rem;line-height:1.65; }
    .mc-export-help-body > p { color:#526175;margin:0 0 .8rem; }
    .mc-export-help-topic { margin-top:.6rem;border:1px solid #dbe3ef;border-radius:.75rem;background:white; }
    .mc-export-help-topic > summary { padding:.8rem;cursor:pointer;font-weight:700; }
    .mc-export-help-answer { padding:0 .8rem .9rem; }
    .mc-export-help-answer h4 { color:#312e81;font-size:.9375rem;margin:0 0 .5rem;font-weight:750; }
    .mc-export-help-answer ol { list-style:decimal;padding-left:1.25rem;margin:.6rem 0; }
    .mc-export-help-answer li + li { margin-top:.5rem; }
    .mc-export-help a { color:#4338ca;text-decoration:underline;text-underline-offset:3px; }
    .mc-export-help-links { display:flex;flex-wrap:wrap;gap:.6rem 1.5rem;margin-top:.8rem; }
    .mc-export-help :is(a,summary):focus-visible { outline:3px solid #818cf8;outline-offset:3px;border-radius:.3rem; }
    .dark .mc-export-help { background:#172033;border-color:#373b6b;color:#dbe4f0; }
    .dark .mc-export-help-topic { background:#111827;border-color:#374151; }
    .dark .mc-export-help :is(strong,h4,a) { color:#c7d2fe; }
    .dark .mc-export-help > summary small,.dark .mc-export-help-body > p { color:#b6c3d5; }
</style>
<details id="archive-help" class="mc-export-help" wire:key="archive-context-help" wire:ignore.self>
    <summary><img src="{{ asset('brand/mobi-welcome.png') }}" alt="" width="48" height="48"><span><strong>Mobi · Help with this export</strong><small>{{ $help['title'] }} · Open step-by-step help</small></span><span class="mc-export-help-chevron" aria-hidden="true">›</span></summary>
    <div class="mc-export-help-body">
        <p>These steps use the archive status shown on this page. Mobi does not change your project or prepare an archive for you.</p>
        <details class="mc-export-help-topic" open wire:ignore.self>
            <summary>Check my download</summary>
            <div class="mc-export-help-answer" data-archive-help-state="{{ $help['state'] }}">
                <h4>{{ $help['title'] }}</h4>
                <ol>@foreach ($help['steps'] as $step)<li>{{ $step }}</li>@endforeach</ol>
                <div class="mc-export-help-links"><a href="{{ $help['anchor'] }}">{{ $help['action'] }}</a><a href="{{ route('marketing.contact') }}" target="_blank" rel="noopener">Contact support (new tab)</a></div>
            </div>
        </details>
        @foreach ($answers as $answer)
            <details id="archive-help-answer-{{ $answer['id'] }}" class="mc-export-help-topic" wire:ignore.self wire:key="export-help-{{ $answer['id'] }}">
                <summary>{{ $answer['question'] }}</summary>
                <div class="mc-export-help-answer">
                    @if (! $canConfigure)<p>Only the project owner or an editor can change the selection or prepare another archive.</p>@endif
                    <p>{{ $answer['answer'] }}</p>
                    <ol>@foreach ($answer['steps'] as $step)<li>{{ $step }}</li>@endforeach</ol>
                    <a href="{{ route('marketing.guide') }}#answer-{{ $answer['id'] }}" target="_blank" rel="noopener">Read in guide (new tab)</a>
                </div>
            </details>
        @endforeach
    </div>
</details>
