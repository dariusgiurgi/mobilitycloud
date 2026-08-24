<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        @page { margin: 24mm 16mm 18mm; }
        body { color:#1e293b; font-size:10px; line-height:1.45; }
        h1 { margin:0; color:#0f172a; font-size:21px; line-height:1.2; }
        h2 { margin:0; color:#1e293b; font-size:12px; line-height:1.35; }
        .eyebrow { color:#4f46e5; font-size:8px; font-weight:bold; letter-spacing:.12em; text-transform:uppercase; }
        .subtle { color:#64748b; }
        .hero { padding:15px 17px; border:1px solid #c7d2fe; border-radius:9px; background:#f7f8ff; }
        .project { margin-top:5px; color:#64748b; font-size:10px; }
        .privacy { margin-top:10px; padding:8px 10px; border-left:3px solid #818cf8; background:#f8fafc; color:#475569; font-size:9px; }
        .metrics { width:100%; border-collapse:separate; border-spacing:7px 0; margin:14px -7px 0; }
        .metric { width:33.33%; padding:10px; border:1px solid #e2e8f0; border-radius:7px; background:#fff; }
        .metric strong { display:block; color:#0f172a; font-size:18px; line-height:1; }
        .metric span { display:block; margin-top:4px; color:#64748b; font-size:8px; text-transform:uppercase; letter-spacing:.06em; }
        .question { margin-top:13px; padding:12px 13px; border:1px solid #e2e8f0; border-radius:8px; page-break-inside:avoid; }
        .question-head { width:100%; border-collapse:collapse; margin-bottom:9px; }
        .answer-count { text-align:right; vertical-align:top; color:#64748b; font-size:8px; white-space:nowrap; }
        .help { margin:3px 0 0; color:#64748b; font-size:9px; }
        .rating-table, .choice-table { width:100%; border-collapse:collapse; }
        .rating-score { width:116px; padding-right:12px; border-right:1px solid #e2e8f0; vertical-align:middle; }
        .rating-score strong { color:#0f172a; font-size:25px; line-height:1; }
        .rating-score span { color:#64748b; font-size:10px; }
        .rating-score small { display:block; margin-top:4px; color:#64748b; font-size:8px; }
        .bar-row td { padding:2px 0; font-size:8px; color:#64748b; }
        .bar-label { width:18px; }
        .bar-count { width:22px; text-align:right; }
        .bar { height:7px; border-radius:4px; background:#eef2ff; overflow:hidden; }
        .bar i { display:block; height:7px; background:#6366f1; }
        .choice-label { color:#334155; font-size:9px; }
        .choice-percent { width:75px; text-align:right; color:#64748b; font-size:8px; }
        .comments { border-collapse:separate; border-spacing:0 6px; width:100%; }
        .comment { padding:8px 10px; border-left:3px solid #c7d2fe; background:#f8fafc; color:#334155; font-size:9px; white-space:pre-wrap; }
        .comment-label { margin-bottom:3px; color:#818cf8; font-size:7px; font-weight:bold; letter-spacing:.07em; text-transform:uppercase; }
        .empty { padding:8px 10px; border:1px dashed #cbd5e1; border-radius:5px; color:#94a3b8; font-size:9px; }
        .footnote { margin:8px 0 0; color:#94a3b8; font-size:8px; }
        .footer { position:fixed; bottom:-10mm; left:0; right:0; color:#94a3b8; font-size:8px; text-align:center; }
    </style>
</head>
<body>
    <div class="hero">
        <div class="eyebrow">Anonymous participant feedback</div>
        <h1>{{ $campaign->title }}</h1>
        <div class="project">{{ $project?->name }} · {{ $mobility?->name }} · Generated {{ now()->format('d M Y') }}</div>
    </div>

    <div class="privacy">This report groups responses by question. It contains no participant names, contact details, response identifiers or response timestamps.</div>

    <table class="metrics"><tr>
        <td class="metric"><strong>{{ $analytics['response_count'] }}</strong><span>{{ \Illuminate\Support\Str::plural('response', $analytics['response_count']) }}</span></td>
        <td class="metric"><strong>{{ $analytics['overall_rating'] ?? '—' }}</strong><span>Overall score{{ $analytics['overall_rating'] !== null ? ' / 5' : '' }}</span></td>
        <td class="metric"><strong>{{ $analytics['question_count'] }}</strong><span>{{ \Illuminate\Support\Str::plural('question', $analytics['question_count']) }}</span></td>
    </tr></table>

    @foreach($analytics['questions'] as $question)
        <section class="question">
            <table class="question-head"><tr>
                <td><h2>{{ $question['label'] }}</h2>@if(filled($question['help'] ?? null))<p class="help">{{ $question['help'] }}</p>@endif</td>
                <td class="answer-count">{{ $question['answer_count'] }} {{ \Illuminate\Support\Str::plural('answer', $question['answer_count']) }}</td>
            </tr></table>

            @if($question['type'] === 'rating')
                @if($question['answer_count'])
                    <table class="rating-table"><tr>
                        <td class="rating-score"><strong>{{ $question['average'] }}</strong><span> / 5</span><small>Average rating</small></td>
                        <td style="padding-left:14px;">@foreach($question['distribution'] as $row)<table class="bar-row" style="width:100%;"><tr><td class="bar-label">{{ $row['score'] }}</td><td><div class="bar"><i style="width:{{ $row['percent'] }}%;"></i></div></td><td class="bar-count">{{ $row['count'] }}</td></tr></table>@endforeach</td>
                    </tr></table>
                @else
                    <div class="empty">No ratings have been submitted yet.</div>
                @endif
            @elseif(in_array($question['type'], ['single_choice', 'multiple_choice', 'yes_no'], true))
                @if($question['answer_count'])
                    <table class="choice-table">@foreach($question['options'] as $option)<tr><td class="choice-label">{{ $option['label'] }}</td><td style="padding:0 10px;"><div class="bar"><i style="width:{{ $option['percent'] }}%;"></i></div></td><td class="choice-percent">{{ $option['count'] }} · {{ $option['percent'] }}%</td></tr>@endforeach</table>
                    @if($question['type'] === 'multiple_choice')<p class="footnote">A respondent may select more than one option.</p>@endif
                @else
                    <div class="empty">No choices have been submitted yet.</div>
                @endif
            @elseif(count($question['answers']))
                <table class="comments">@foreach($question['answers'] as $index => $answer)<tr><td class="comment"><div class="comment-label">Response {{ $index + 1 }}</div>{{ $answer }}</td></tr>@endforeach</table>
            @else
                <div class="empty">No written responses have been submitted yet.</div>
            @endif
        </section>
    @endforeach

    <div class="footer">{{ $project?->name }} · Anonymous feedback report · MobilityCloud</div>
</body>
</html>
