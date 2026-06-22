<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { font-size: 11px; color: #18181b; margin: 0; line-height: 1.6; }
    h1 { font-size: 19px; margin: 0 0 2px; color: #1f2937; }
    .meta { font-size: 10px; color: #6b7280; }
    .cat { font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: .05em; color: #6366f1; margin: 18px 0 6px; }
    .q-title { font-weight: bold; font-size: 12px; margin: 12px 0 4px; color: #1f2937; }
    .q-content { font-size: 11px; white-space: pre-wrap; margin-bottom: 4px; }
    .q-meta { font-size: 9px; color: #9ca3af; margin-bottom: 8px; }
    .empty { font-style: italic; color: #9ca3af; }
    @include('pdf.partials.document-theme')
</style></head><body>

<div class="mc-doc-header">
    <span class="mc-doc-brand">MobilityCloud</span>
    <span class="mc-doc-context">Application document</span>
</div>
<div class="mc-doc-footer">
    <span class="mc-doc-footer-left">{{ $project->name }} · Powered by Xeotype</span>
    <span class="mc-doc-footer-right">Page <span class="mc-doc-page"></span></span>
</div>

<div class="mc-doc-title">
    <h1>Project Application</h1>
    <div class="meta">
        <strong>{{ $project->name }}</strong>@if($project->acronym) · {{ $project->acronym }}@endif<br>
        Generated {{ now()->format('d M Y, H:i') }}
    </div>
</div>

@php $currentCat = null; @endphp
@forelse($sections as $sec)
    @if($sec->category && $sec->category !== $currentCat)
        @php $currentCat = $sec->category; @endphp
        <div class="cat">{{ $currentCat }}</div>
    @endif
    <div class="q-title">{{ $sec->title }}</div>
    @if(trim(strip_tags($sec->content ?? '')) !== '')
        <div class="q-content">{{ $sec->content }}</div>
    @else
        <div class="q-content empty">— not answered —</div>
    @endif
    <div class="q-meta">{{ $sec->word_count }} words · {{ $sec->char_count }}@if($sec->char_limit)/{{ $sec->char_limit }}@endif characters</div>
@empty
    <p class="empty">No application sections.</p>
@endforelse

</body></html>
