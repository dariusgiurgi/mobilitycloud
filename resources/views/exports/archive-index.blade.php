<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Security-Policy" content="default-src 'none'; style-src 'unsafe-inline'; img-src data:; base-uri 'none'">
    <title>{{ $payload['project']['name'] }} — Project archive</title>
    <style>
        body{font:16px/1.6 system-ui,sans-serif;color:#172033;max-width:1080px;margin:40px auto;padding:0 24px;background:#fff}
        h1{font-size:32px;line-height:1.2}h2{margin-top:36px;border-bottom:2px solid #dce5ef;padding-bottom:8px}h3{margin-top:24px}
        .meta{color:#526176}.notice{padding:16px;background:#f1f5f9;border-left:4px solid #2563eb}
        table{border-collapse:collapse;width:100%;font-size:14px}td,th{border-bottom:1px solid #dce5ef;padding:10px;text-align:left;vertical-align:top;overflow-wrap:anywhere}
        th{background:#f1f5f9}a{color:#1d4ed8}.text{white-space:pre-wrap}code{font-size:12px;overflow-wrap:anywhere}
        @media print{body{margin:0;font-size:11px}tr{break-inside:avoid}}
    </style>
</head>
<body>
    <p class="meta">MOBILITYCLOUD · FINAL PROJECT ARCHIVE</p>
    <h1>{{ $payload['project']['name'] }}</h1>
    <p class="meta">{{ $payload['project']['grant_ref'] ?: 'No grant reference' }} · {{ $payload['project']['start_date'] }} — {{ $payload['project']['end_date'] }}</p>
    <p><strong>Data snapshot:</strong> {{ $payload['snapshot_at'] }}<br><strong>Project status:</strong> {{ $payload['project']['status'] }}</p>
    <p class="notice">This is a snapshot of the selected project material, not a live view. Later changes require a new export. The archive may contain confidential personal and financial information. Keep it in a secure location.</p>
    @if($payload['project']['description'])<p class="text">{{ $payload['project']['description'] }}</p>@endif
    <h2>Included categories</h2>
    <ul>@foreach($payload['included_sections'] as $key => $included)<li>{{ ucwords(str_replace('_', ' ', $key)) }}: <strong>{{ $included ? 'Included' : 'Not selected' }}</strong></li>@endforeach</ul>
    @if(!empty($payload['project']['partner_orgs']))
        <h2>Partner organisations</h2>
        @foreach($payload['project']['partner_orgs'] as $partner)<p>{{ is_array($partner) ? ($partner['name'] ?? '') : $partner }}</p>@endforeach
    @endif
    @if(count($payload['application_flows']))
        <h2>Application activity flows</h2>
        @foreach($payload['application_flows'] as $flow)
            <h3>{{ $flow['flow_id'] ?? 'Activity flow' }} · {{ $flow['group_label'] ?? '' }}</h3>
            <table><tbody>@foreach($flow as $field => $value)
                @if(is_scalar($value) && $value !== '')<tr><th>{{ ucwords(str_replace('_', ' ', $field)) }}</th><td>{{ is_bool($value) ? ($value ? 'Yes' : 'No') : $value }}</td></tr>@endif
            @endforeach</tbody></table>
        @endforeach
    @endif
    @if(count($payload['mobilities']))
        <h2>Mobilities</h2>
        @foreach($payload['mobilities'] as $mobility)
            <h3>{{ $mobility['name'] }}</h3>
            <p>{{ $mobility['start_date'] }} — {{ $mobility['end_date'] }} · {{ $mobility['destination_country'] }} · {{ $mobility['host_organisation'] }}</p>
            @if(!empty($mobility['workspace_data']['report']))<p class="text">{{ $mobility['workspace_data']['report'] }}</p>@endif
            @foreach(['photo_folder_url' => 'Photo folder', 'final_video_url' => 'Final video'] as $key => $label)
                @if(!empty($mobility['workspace_data'][$key]))<p><strong>{{ $label }}:</strong> {{ $mobility['workspace_data'][$key] }}</p>@endif
            @endforeach
            @foreach(($mobility['workspace_data']['photo_folder_links'] ?? []) as $link)
                <p><strong>{{ $link['label'] ?? 'Photo folder' }}:</strong> {{ $link['url'] ?? '' }}</p>
            @endforeach
            @foreach(($mobility['workspace_data']['dissemination_reports'] ?? []) as $organisation => $text)
                <p><strong>{{ $organisation }}</strong></p><p class="text">{{ $text }}</p>
            @endforeach
            @foreach(($mobility['workspace_data']['evidence_days'] ?? []) as $day)
                <h3>{{ $day['date'] ?? '' }} · {{ $day['title'] ?? 'Activity day' }}</h3>
                <p class="text">{{ $day['description'] ?? '' }}</p>
                <p class="text">{{ $day['observations'] ?? '' }}</p>
                @foreach(($day['links'] ?? []) as $link)<p>{{ $link['label'] ?? '' }}: {{ $link['url'] ?? '' }}</p>@endforeach
            @endforeach
        @endforeach
    @endif
    <h2>Documents and files</h2>
    <p>Extract the complete ZIP before opening these links. Structured records and deleted-expense history are available in <a href="00-project-data/project-data.json">project-data.json</a>. Deleted expenses are excluded from the financial PDF totals.</p>
    <table><thead><tr><th>Document</th><th>Type</th><th>Size</th><th>Location</th></tr></thead><tbody>
    @foreach($payload['file_index'] as $file)
        @php($relative = implode('/', array_slice(explode('/', $file['archive_path']), 1)))
        <tr><td><a href="{{ implode('/', array_map('rawurlencode', explode('/', $relative))) }}">{{ $file['original_name'] }}</a></td><td>{{ $file['slot'] }}</td><td>{{ number_format($file['size'] / 1024, 1) }} KB</td><td><code>{{ $relative }}</code></td></tr>
    @endforeach
    </tbody></table>
    <p class="meta">Format version {{ $payload['format_version'] }} · Individual file SHA-256 checksums are recorded in project-data.json.</p>
</body></html>
