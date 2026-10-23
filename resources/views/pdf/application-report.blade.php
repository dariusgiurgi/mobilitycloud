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
    .q-table-title { font-size: 10px; font-weight: bold; color: #334155; margin: 8px 0 4px; }
    .q-table { width: 100%; border-collapse: collapse; margin: 0 0 8px; font-size: 9px; }
    .q-table th { text-align: left; background: #eef2ff; color: #334155; border: 1px solid #cbd5e1; padding: 4px; }
    .q-table td { vertical-align: top; border: 1px solid #cbd5e1; padding: 4px; }
    .empty { font-style: italic; color: #9ca3af; }

    /* Keep labels with the following content, while long answers can flow across pages. */
    .cat, h2, h3, .q-title, .q-table-title { page-break-after: avoid; }
    .q-content, .answer { orphans: 2; widows: 2; }
    thead { display: table-header-group; }
    @include('pdf.partials.document-theme')
</style></head><body>

@include('pdf.partials.document-header', ['project' => $project, 'context' => 'Application document', 'footerLeft' => $project->name.' · '.($project->documentSetting('footer_text', 'Generated with MobilityCloud'))])

<div class="mc-doc-title">
    <h1>Project Application</h1>
    <div class="meta">
        <strong>{{ $project->name }}</strong>@if($project->acronym) · {{ $project->acronym }}@endif<br>
        Generated {{ now()->format('d M Y, H:i') }}
    </div>
</div>

@forelse(\App\Support\ApplicationExportLayout::sections($sections) as $entry)
    @php $sec = $entry['section']; @endphp
    @if($entry['heading'] !== null)
        <div class="cat">{{ $entry['heading'] }}</div>
    @endif
    @if(! $entry['is_heading'])
        <div class="q-title">{{ $sec->title }}</div>
    @endif
    @if(trim((string) $sec->content) !== '')
        <div class="q-content">{{ $sec->content }}</div>
    @elseif(! $entry['is_heading'])
        <div class="q-content empty">— not answered —</div>
    @endif
    @foreach(\App\Support\ApplicationTableDefinitions::forSection($sec) as $tableDef)
        @php $rows = \App\Support\ApplicationTableDefinitions::filledRows($sec, $tableDef['key']); @endphp
        @if(count($rows))
            <div class="q-table-title">{{ $tableDef['label'] }}</div>
            <table class="q-table">
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
    @if(! $entry['is_heading'])
    <div class="q-meta">{{ $sec->word_count }} words · {{ $sec->char_count }}@if($sec->char_limit)/{{ $sec->char_limit }}@endif characters</div>
    @endif
@empty
    <p class="empty">No application sections.</p>
@endforelse

</body></html>
