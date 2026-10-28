<article class="mc-guide-chapter" id="{{ $chapter['id'] }}" data-guide-search-item data-guide-anchor="{{ $chapter['id'] }}" data-guide-search-title="{{ $chapter['title'] }}">
    <div class="mc-guide-chapter-number">{{ $chapterNumber }}</div>
    <div class="mc-guide-chapter-content">
        <p class="mc-eyebrow">Practical instructions</p>
        <h2>{{ $chapter['title'] }}</h2>
        <p class="mc-guide-intro">{{ $chapter['intro'] }}</p>
        @if ($chapter['id'] === 'export-guide')
            <figure class="mc-guide-shot" style="margin-top:1rem;">
                <a href="{{ asset('images/guide/finalisation-export-help.png') }}" target="_blank" rel="noopener" aria-label="Open the annotated export screen at full size (new tab)">
                    <img src="{{ asset('images/guide/finalisation-export-help.png') }}" alt="Finalisation screen: 1 downloads the saved ZIP, 2 prepares a fresh archive, and 3 opens Mobi’s help for the current export status." loading="lazy">
                </a>
                <figcaption><span>Actual screen · sample project</span> 1 · Download the prepared ZIP. 2 · Include later changes with Prepare fresh archive. 3 · Open Mobi for steps matched to the current status. Select the image to enlarge it.</figcaption>
            </figure>
        @endif
        <div class="mc-guide-answers">
            @foreach($chapter['items'] as $answer)
                <details id="answer-{{ $answer['id'] }}" data-guide-search-item data-guide-anchor="answer-{{ $answer['id'] }}" data-guide-search-title="{{ $answer['question'] }}" data-guide-keywords="{{ $answer['keywords'] }}">
                    <summary>{{ $answer['question'] }}</summary>
                    <div class="mc-guide-answer-body">
                        <p>{{ $answer['answer'] }}</p>
                        <ol>@foreach($answer['steps'] as $step)<li>{{ $step }}</li>@endforeach</ol>
                    </div>
                </details>
            @endforeach
        </div>
    </div>
</article>
