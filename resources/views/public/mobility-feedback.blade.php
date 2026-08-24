<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $campaign->title }} — MobilityCloud</title>
    <style>
        :root{color-scheme:light;--brand:#4f46e5;--ink:#172033;--muted:#667085;--line:#e2e8f0;--bg:#f7f8fb}*{box-sizing:border-box}body{margin:0;background:radial-gradient(circle at top left,rgba(79,70,229,.12),transparent 34rem),var(--bg);color:var(--ink);font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.wrap{width:min(760px,calc(100% - 32px));margin:0 auto;padding:42px 0 56px}.brand{display:flex;align-items:center;gap:.65rem;font-size:1.05rem;font-weight:850;margin-bottom:1.35rem}.mark{display:grid;place-items:center;width:34px;height:34px;border-radius:12px;background:#111827;color:#fff}.card{overflow:hidden;background:#fff;border:1px solid rgba(17,24,39,.08);border-radius:28px;box-shadow:0 24px 70px rgba(15,23,42,.08)}.hero{padding:34px 36px;border-bottom:1px solid var(--line);background:linear-gradient(135deg,rgba(79,70,229,.08),rgba(14,165,233,.05))}.eyebrow{margin:0 0 .55rem;color:var(--brand);font-size:.75rem;font-weight:850;letter-spacing:.08em;text-transform:uppercase}h1{margin:0;font-size:clamp(1.9rem,4vw,2.7rem);line-height:1.05;letter-spacing:-.045em}.subtitle{max-width:42rem;margin:.85rem 0 0;color:var(--muted);font-size:1rem;line-height:1.6}.body{padding:30px 36px 34px}.notice{padding:13px 15px;margin-bottom:22px;border:1px solid #bbf7d0;border-radius:16px;background:#ecfdf5;color:#047857;font-size:.9rem;font-weight:700;line-height:1.5}.notice.closed{border-color:#fde68a;background:#fffbeb;color:#92400e}.anonymous{padding:13px 15px;margin-bottom:22px;border:1px solid #c7d2fe;border-radius:16px;background:#f5f7ff;color:#3730a3;font-size:.86rem;line-height:1.5}.question{padding:18px 0;border-top:1px solid var(--line)}.question:first-child{border-top:0;padding-top:0}.question-label{display:block;font-size:1rem;font-weight:780;line-height:1.4}.required{color:#dc2626}.help{margin:.3rem 0 .65rem;color:var(--muted);font-size:.88rem;line-height:1.45}input[type=text],textarea,select{width:100%;border:1px solid #d0d5dd;border-radius:13px;background:#fff;padding:12px 13px;font:inherit;font-size:.95rem;color:#111827;outline:none}textarea{min-height:110px;resize:vertical}input:focus,textarea:focus,select:focus{border-color:var(--brand);box-shadow:0 0 0 4px rgba(79,70,229,.12)}.choices{display:grid;gap:.55rem}.choice{display:flex;align-items:flex-start;gap:.65rem;padding:12px 13px;border:1px solid var(--line);border-radius:13px;background:#fafafa;cursor:pointer;line-height:1.4}.choice input{margin-top:.18rem;accent-color:var(--brand)}.rating{display:flex;gap:.5rem;flex-wrap:wrap}.rating label{display:grid;place-items:center;width:44px;height:42px;border:1px solid #d0d5dd;border-radius:12px;background:#fff;font-weight:780;cursor:pointer}.rating input{position:absolute;opacity:0;pointer-events:none}.rating input:checked+span{display:grid;place-items:center;width:42px;height:40px;margin:-1px;border-radius:11px;background:var(--brand);color:white}.error{margin-top:.35rem;color:#dc2626;font-size:.8rem}.actions{display:flex;justify-content:flex-end;margin-top:26px}button{border:0;border-radius:14px;padding:13px 20px;background:var(--brand);color:#fff;font:inherit;font-weight:850;cursor:pointer;box-shadow:0 12px 30px rgba(79,70,229,.22)}button:hover{background:#4338ca}.fine{margin:18px 0 0;color:var(--muted);font-size:.79rem;line-height:1.5}@media(max-width:640px){.wrap{width:min(100% - 20px,760px);padding-top:20px}.hero,.body{padding:24px 20px}}
    </style>
</head>
<body>
    <main class="wrap">
        <div class="brand"><span class="mark">M</span> MobilityCloud</div>
        <section class="card">
            <div class="hero">
                <p class="eyebrow">Anonymous mobility feedback</p>
                <h1>{{ $campaign->title }}</h1>
                <p class="subtitle">{{ $project?->name }} · {{ $mobility?->name }}@if($mobility?->start_date || $mobility?->end_date) · {{ $mobility?->start_date?->format('d M Y') ?: 'Date to be confirmed' }} – {{ $mobility?->end_date?->format('d M Y') ?: 'Date to be confirmed' }}@endif</p>
            </div>
            <div class="body">
                @if(session('submitted'))
                    <div class="notice">{{ data_get($campaign->form_snapshot, 'thank_you_text') ?: 'Thank you for sharing your feedback. Your response was recorded anonymously.' }}</div>
                @elseif($closed)
                    <div class="notice closed">This feedback form is currently closed. Please contact the project team if you need a new link.</div>
                @else
                    <div class="anonymous"><strong>Your response is anonymous.</strong><br>We do not ask for or store your name, email, participant profile, IP address or account with this feedback. Please avoid adding identifying details in open answers if you want to remain anonymous.</div>
                    @if(filled(data_get($campaign->form_snapshot, 'intro_text')))
                        <p class="subtitle" style="margin:0 0 1.25rem;">{{ data_get($campaign->form_snapshot, 'intro_text') }}</p>
                    @endif
                    <form method="POST" action="{{ route('public.mobility-feedback.store', $campaign->public_token) }}">
                        @csrf
                        @foreach($questions as $question)
                            @php($id = $question['id'])
                            @php($type = $question['type'])
                            <div class="question">
                                <label class="question-label" for="{{ $id }}">{{ $question['label'] }} @if($question['required'])<span class="required">*</span>@endif</label>
                                @if(filled($question['help'] ?? null))<p class="help">{{ $question['help'] }}</p>@endif
                                @if($type === 'rating')
                                    <div class="rating" id="{{ $id }}">@foreach(range(1, 5) as $score)<label><input type="radio" name="answers[{{ $id }}]" value="{{ $score }}" @checked((string) old('answers.'.$id) === (string) $score) {{ $question['required'] ? 'required' : '' }}><span>{{ $score }}</span></label>@endforeach</div>
                                @elseif($type === 'single_choice')
                                    <div class="choices">@foreach($question['options'] as $option)<label class="choice"><input type="radio" name="answers[{{ $id }}]" value="{{ $option }}" @checked(old('answers.'.$id) === $option) {{ $question['required'] ? 'required' : '' }}><span>{{ $option }}</span></label>@endforeach</div>
                                @elseif($type === 'multiple_choice')
                                    <div class="choices">@foreach($question['options'] as $option)<label class="choice"><input type="checkbox" name="answers[{{ $id }}][]" value="{{ $option }}" @checked(in_array($option, old('answers.'.$id, [])))><span>{{ $option }}</span></label>@endforeach</div>
                                @elseif($type === 'yes_no')
                                    <div class="choices"><label class="choice"><input type="radio" name="answers[{{ $id }}]" value="yes" @checked(old('answers.'.$id) === 'yes') {{ $question['required'] ? 'required' : '' }}><span>Yes</span></label><label class="choice"><input type="radio" name="answers[{{ $id }}]" value="no" @checked(old('answers.'.$id) === 'no') {{ $question['required'] ? 'required' : '' }}><span>No</span></label></div>
                                @elseif($type === 'long_text')
                                    <textarea id="{{ $id }}" name="answers[{{ $id }}]" {{ $question['required'] ? 'required' : '' }}>{{ old('answers.'.$id) }}</textarea>
                                @else
                                    <input id="{{ $id }}" type="text" name="answers[{{ $id }}]" value="{{ old('answers.'.$id) }}" {{ $question['required'] ? 'required' : '' }}>
                                @endif
                                @error('answers.'.$id)<div class="error">{{ $message }}</div>@enderror
                            </div>
                        @endforeach
                        <p class="fine">Submitting this form records only the answers above for this mobility. Do not include your name or contact details in an open answer.</p>
                        <div class="actions"><button type="submit">Send anonymous feedback</button></div>
                    </form>
                @endif
            </div>
        </section>
    </main>
</body>
</html>
