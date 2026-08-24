@php
    $report = $analytics ?? ['response_count' => 0, 'question_count' => 0, 'overall_rating' => null, 'questions' => []];
@endphp

<div class="mc-feedback-report">
    <div class="mc-feedback-report-overview">
        <div>
            <div class="mc-feedback-eyebrow">Anonymous feedback summary</div>
            <p class="mc-feedback-report-note">Responses are shown by question only. They are never connected to a participant.</p>
        </div>
        <div class="mc-feedback-report-metrics">
            <div class="mc-feedback-report-metric">
                <strong>{{ $report['response_count'] }}</strong>
                <span>{{ \Illuminate\Support\Str::plural('response', $report['response_count']) }}</span>
            </div>
            <div class="mc-feedback-report-metric">
                <strong>{{ $report['overall_rating'] ?? '—' }}</strong>
                <span>overall score{{ $report['overall_rating'] !== null ? ' / 5' : '' }}</span>
            </div>
            <div class="mc-feedback-report-metric">
                <strong>{{ $report['question_count'] }}</strong>
                <span>{{ \Illuminate\Support\Str::plural('question', $report['question_count']) }}</span>
            </div>
        </div>
    </div>

    @forelse($report['questions'] as $question)
        <article class="mc-feedback-question-report">
            <header class="mc-feedback-question-report-header">
                <div>
                    <h3>{{ $question['label'] }}</h3>
                    @if(filled($question['help'] ?? null))
                        <p>{{ $question['help'] }}</p>
                    @endif
                </div>
                <span>{{ $question['answer_count'] }} {{ \Illuminate\Support\Str::plural('answer', $question['answer_count']) }}</span>
            </header>

            @if($question['type'] === 'rating')
                @if($question['answer_count'])
                    <div class="mc-feedback-rating-layout">
                        <div class="mc-feedback-rating-score">
                            <strong>{{ $question['average'] }}</strong><span>/ 5</span>
                            <small>Average rating</small>
                        </div>
                        <div class="mc-feedback-distribution">
                            @foreach($question['distribution'] as $row)
                                <div class="mc-feedback-distribution-row">
                                    <span>{{ $row['score'] }}</span>
                                    <div><i style="width:{{ $row['percent'] }}%"></i></div>
                                    <b>{{ $row['count'] }}</b>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="mc-feedback-no-answer">No ratings have been submitted yet.</div>
                @endif
            @elseif(in_array($question['type'], ['single_choice', 'multiple_choice', 'yes_no'], true))
                @if($question['answer_count'])
                    <div class="mc-feedback-choice-list">
                        @foreach($question['options'] as $option)
                            <div class="mc-feedback-choice-row">
                                <div><span>{{ $option['label'] }}</span><b>{{ $option['count'] }} · {{ $option['percent'] }}%</b></div>
                                <div><i style="width:{{ $option['percent'] }}%"></i></div>
                            </div>
                        @endforeach
                    </div>
                    @if($question['type'] === 'multiple_choice')
                        <p class="mc-feedback-report-footnote">A respondent may select more than one option.</p>
                    @endif
                @else
                    <div class="mc-feedback-no-answer">No choices have been submitted yet.</div>
                @endif
            @else
                @if(count($question['answers']))
                    <div class="mc-feedback-comment-list">
                        @foreach($question['answers'] as $index => $answer)
                            <blockquote>
                                <span>Response {{ $index + 1 }}</span>
                                <p>{{ $answer }}</p>
                            </blockquote>
                        @endforeach
                    </div>
                @else
                    <div class="mc-feedback-no-answer">No written responses have been submitted yet.</div>
                @endif
            @endif
        </article>
    @empty
        <div class="mc-feedback-empty">This feedback form has no questions.</div>
    @endforelse
</div>

@once
    <style>
        .mc-feedback-report{display:grid;gap:.72rem}.mc-feedback-report-overview{display:flex;justify-content:space-between;gap:1rem;align-items:center;padding:.85rem .9rem;border:1px solid rgba(99,102,241,.18);border-radius:.8rem;background:linear-gradient(115deg,#f6f7ff,#fbfdff)}.mc-feedback-eyebrow{font-size:.62rem;font-weight:850;letter-spacing:.08em;text-transform:uppercase;color:#6366f1}.mc-feedback-report-note{margin:.2rem 0 0;color:#64748b;font-size:.69rem;line-height:1.45}.mc-feedback-report-metrics{display:flex;gap:.85rem;flex-wrap:wrap}.mc-feedback-report-metric{min-width:58px}.mc-feedback-report-metric strong{display:block;font-size:1.05rem;font-weight:850;color:#111827}.mc-feedback-report-metric span{display:block;margin-top:.08rem;color:#64748b;font-size:.59rem;line-height:1.25}.mc-feedback-question-report{padding:.85rem .9rem;border:1px solid rgba(148,163,184,.21);border-radius:.8rem;background:#fff}.mc-feedback-question-report-header{display:flex;justify-content:space-between;align-items:flex-start;gap:.8rem;margin-bottom:.7rem}.mc-feedback-question-report-header h3{margin:0;font-size:.78rem;font-weight:800;line-height:1.35;color:#1e293b}.mc-feedback-question-report-header p{margin:.14rem 0 0;color:#64748b;font-size:.65rem;line-height:1.42}.mc-feedback-question-report-header>span{flex:0 0 auto;padding:.18rem .38rem;border-radius:999px;background:#f1f5f9;color:#64748b;font-size:.58rem;font-weight:750}.mc-feedback-rating-layout{display:grid;grid-template-columns:116px minmax(0,1fr);gap:1rem;align-items:center}.mc-feedback-rating-score{padding-right:1rem;border-right:1px solid rgba(148,163,184,.18)}.mc-feedback-rating-score strong{font-size:1.65rem;line-height:1;color:#111827}.mc-feedback-rating-score span{font-size:.72rem;font-weight:750;color:#64748b;margin-left:.14rem}.mc-feedback-rating-score small{display:block;margin-top:.28rem;font-size:.6rem;color:#64748b}.mc-feedback-distribution{display:grid;gap:.32rem}.mc-feedback-distribution-row{display:grid;grid-template-columns:12px minmax(0,1fr) 18px;align-items:center;gap:.4rem;color:#64748b;font-size:.61rem}.mc-feedback-distribution-row>div,.mc-feedback-choice-row>div:last-child{height:7px;overflow:hidden;border-radius:999px;background:#eef2ff}.mc-feedback-distribution-row i,.mc-feedback-choice-row i{display:block;height:100%;min-width:0;border-radius:inherit;background:linear-gradient(90deg,#818cf8,#6366f1)}.mc-feedback-distribution-row b{font-size:.61rem;text-align:right;color:#475569}.mc-feedback-choice-list{display:grid;gap:.6rem}.mc-feedback-choice-row>div:first-child{display:flex;justify-content:space-between;gap:.8rem;margin-bottom:.24rem;color:#475569;font-size:.67rem}.mc-feedback-choice-row b{font-weight:750;color:#64748b}.mc-feedback-report-footnote{margin:.55rem 0 0;color:#94a3b8;font-size:.59rem}.mc-feedback-comment-list{display:grid;gap:.45rem}.mc-feedback-comment-list blockquote{margin:0;padding:.58rem .65rem;border-left:3px solid #c7d2fe;border-radius:0 .55rem .55rem 0;background:#f8fafc}.mc-feedback-comment-list span{display:block;font-size:.57rem;font-weight:800;letter-spacing:.04em;text-transform:uppercase;color:#818cf8}.mc-feedback-comment-list p{margin:.18rem 0 0;font-size:.69rem;line-height:1.5;white-space:pre-wrap;color:#334155}.mc-feedback-no-answer{padding:.65rem .7rem;border:1px dashed rgba(148,163,184,.36);border-radius:.55rem;color:#94a3b8;font-size:.66rem}@media(max-width:640px){.mc-feedback-report-overview{align-items:flex-start;flex-direction:column}.mc-feedback-rating-layout{grid-template-columns:1fr;gap:.65rem}.mc-feedback-rating-score{padding:0;border:0}.mc-feedback-question-report-header{flex-direction:column;gap:.35rem}}
    </style>
@endonce
