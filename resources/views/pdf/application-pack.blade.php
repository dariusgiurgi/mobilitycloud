<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { font-size: 10.5px; color: #18181b; margin: 0; line-height: 1.55; }
    h1 { font-size: 19px; margin: 0 0 2px; color: #1f2937; }
    h2 { font-size: 13px; margin: 18px 0 7px; color: #312e81; border-bottom: 1px solid #c7d2fe; padding-bottom: 3px; }
    h3 { font-size: 11px; margin: 12px 0 4px; color: #1f2937; }
    table { width: 100%; border-collapse: collapse; margin: 5px 0 9px; font-size: 9px; }
    th { text-align: left; background: #eef2ff; color: #334155; border: 1px solid #cbd5e1; padding: 4px; }
    td { vertical-align: top; border: 1px solid #cbd5e1; padding: 4px; }
    .meta { font-size: 10px; color: #6b7280; }
    .muted { color: #6b7280; }
    .answer { white-space: pre-wrap; }
    .empty { font-style: italic; color: #9ca3af; }
    .kpi { display: inline-block; border: 1px solid #cbd5e1; border-radius: 5px; padding: 5px 7px; margin: 0 5px 5px 0; }
    .kpi strong { display:block; font-size: 13px; color:#111827; }
    @include('pdf.partials.document-theme')
</style></head><body>

@include('pdf.partials.document-header', ['workspace' => $project->workspace, 'context' => 'Application pack', 'footerLeft' => $project->name.' · '.($project->workspace->documentSetting('footer_text', 'Generated with MobilityCloud'))])

<div class="mc-doc-title">
    <h1>Application Pack</h1>
    <div class="meta">
        <strong>{{ $project->name }}</strong>@if($project->acronym) · {{ $project->acronym }}@endif<br>
        {{ strtoupper($project->ka_action ?: 'Application') }} · Generated {{ now()->format('d M Y, H:i') }}
    </div>
</div>

<h2>1. Project snapshot</h2>
<div class="kpi"><span class="muted">Workspace</span><strong>{{ $project->workspace?->name ?: '—' }}</strong></div>
<div class="kpi"><span class="muted">Participants</span><strong>{{ $project->participants->count() }}</strong></div>
<div class="kpi"><span class="muted">Budget</span><strong>{{ number_format((float) ($project->approved_budget ?: $project->total_budget), 2) }} EUR</strong></div>
<div class="kpi"><span class="muted">Flows</span><strong>{{ count($flows) }}</strong></div>
<p class="muted">
    @if($project->mobility_start_date || $project->mobility_end_date)
        Mobility: {{ $project->mobility_start_date?->format('d M Y') ?: 'TBC' }} – {{ $project->mobility_end_date?->format('d M Y') ?: 'TBC' }}.
    @elseif($project->start_date || $project->end_date)
        Project period: {{ $project->start_date?->format('d M Y') ?: 'TBC' }} – {{ $project->end_date?->format('d M Y') ?: 'TBC' }}.
    @endif
</p>

@if($project->description)
    <p>{{ $project->description }}</p>
@endif

<h2>2. Mobility activities and flows</h2>
@if(count($flows))
    <table>
        <thead>
            <tr>
                <th>Activity / flow</th>
                <th>Group</th>
                <th>Route</th>
                <th>Dates</th>
                <th>Participants</th>
                <th>Support</th>
                <th>Output</th>
            </tr>
        </thead>
        <tbody>
            @foreach($flows as $flow)
                <tr>
                    <td>{{ $flow['activity_id'] ?? '—' }} / {{ $flow['flow_id'] ?? '—' }}<br><span class="muted">{{ $flow['activity_type'] ?? '' }}</span></td>
                    <td>{{ $flow['group_label'] ?? '—' }}</td>
                    <td>{{ $flow['origin_country'] ?? '—' }} → {{ $flow['destination_country'] ?? '—' }}<br><span class="muted">{{ $flow['distance_band'] ?? '' }} @if(! empty($flow['green_travel'])) · green @endif</span></td>
                    <td>{{ $flow['start_date'] ?? 'TBC' }} – {{ $flow['end_date'] ?? 'TBC' }}<br><span class="muted">{{ $flow['duration_days'] ?? '0' }} days + {{ $flow['travel_days'] ?? '0' }} travel</span></td>
                    <td>{{ $flow['participants_count'] ?? '0' }}</td>
                    <td>{{ $flow['fewer_opportunities_count'] ?? '0' }} fewer opp.<br>{{ $flow['group_leaders_count'] ?? '0' }} leaders/support</td>
                    <td>{{ $flow['learning_output'] ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <p class="empty">No structured activity flows have been added yet.</p>
@endif

<h2>3. Budget overview</h2>
<table>
    <thead><tr><th>Budget basket</th><th>Allocated</th><th>Recorded spent</th><th>Remaining</th></tr></thead>
    <tbody>
        @forelse($budgetLines as $line)
            <tr>
                <td>{{ $line['title'] }}</td>
                <td>{{ number_format($line['allocated'], 2) }} EUR</td>
                <td>{{ number_format($line['spent'], 2) }} EUR</td>
                <td>{{ number_format($line['remaining'], 2) }} EUR</td>
            </tr>
        @empty
            <tr><td colspan="4" class="empty">No budget lines.</td></tr>
        @endforelse
    </tbody>
</table>

<h2>4. Official application answers</h2>
@php $currentCat = null; @endphp
@forelse($sections as $sec)
    @if($sec->category && $sec->category !== $currentCat)
        @php $currentCat = $sec->category; @endphp
        <h3>{{ $currentCat }}</h3>
    @endif
    <p><strong>{{ $sec->title }}</strong></p>
    @if(trim(strip_tags($sec->content ?? '')) !== '')
        <div class="answer">{{ $sec->content }}</div>
    @else
        <div class="answer empty">— not answered —</div>
    @endif
    @foreach(\App\Support\ApplicationTableDefinitions::forSection($sec) as $tableDef)
        @php $rows = \App\Support\ApplicationTableDefinitions::filledRows($sec, $tableDef['key']); @endphp
        @if(count($rows))
            <p><strong>{{ $tableDef['label'] }}</strong></p>
            <table>
                <thead>
                    <tr>
                        @foreach($tableDef['columns'] as $column)
                            <th>{{ $column['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            @foreach($tableDef['columns'] as $column)
                                <td>{{ $row[$column['field']] ?? '' }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endforeach
@empty
    <p class="empty">No application sections.</p>
@endforelse

</body></html>
