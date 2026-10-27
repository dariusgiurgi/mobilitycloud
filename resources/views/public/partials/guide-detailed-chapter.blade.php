<article class="mc-guide-chapter" id="{{ $chapter['id'] }}" data-guide-search-item data-guide-anchor="{{ $chapter['id'] }}" data-guide-search-title="{{ $chapter['title'] }}">
    <div class="mc-guide-chapter-number">{{ $chapterNumber }}</div>
    <div class="mc-guide-chapter-content">
        <p class="mc-eyebrow">Practical instructions</p>
        <h2>{{ $chapter['title'] }}</h2>
        <p class="mc-guide-intro">{{ $chapter['intro'] }}</p>
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
