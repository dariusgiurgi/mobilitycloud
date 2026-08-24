<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $template['label'] }}</title>
    <style>
        @page { margin: 17mm 16mm 16mm; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #18181b; font-size: 9.4px; line-height: 1.45; margin: 0; }
        .brand { color: {{ $project->documentSetting('accent_color', '#4f46e5') }}; font-size: 9px; font-weight: bold; letter-spacing: .09em; text-transform: uppercase; }
        .logo { max-height: 28px; max-width: 130px; vertical-align: middle; }
        h1 { font-size: 19px; margin: 7px 0 3px; letter-spacing: -.02em; }
        h2 { color: #3730a3; font-size: 11px; margin: 13px 0 6px; }
        p { margin: 0 0 7px; }
        .notice { margin: 10px 0 12px; padding: 7px 9px; border: 1px solid #c7d2fe; background: #f5f7ff; color: #3730a3; font-size: 8.2px; }
        .project { width: 100%; border-collapse: collapse; margin: 8px 0 12px; }
        .project td { width: 50%; padding: 4px 10px 4px 0; vertical-align: top; }
        .label { color: #71717a; font-size: 7.3px; font-weight: bold; letter-spacing: .05em; text-transform: uppercase; }
        .value { font-weight: bold; font-size: 9px; margin-top: 1px; }
        .line { display: inline-block; min-width: 150px; height: 13px; border-bottom: 1px solid #64748b; vertical-align: bottom; }
        .line-wide { display: block; width: 100%; height: 15px; border-bottom: 1px solid #64748b; margin: 4px 0 8px; }
        .box { height: 58px; border: 1px solid #94a3b8; border-radius: 3px; margin: 4px 0 9px; }
        .check { display: inline-block; width: 10px; height: 10px; border: 1px solid #64748b; margin-right: 5px; vertical-align: -1px; }
        .signatures { width: 100%; margin-top: 20px; border-collapse: collapse; page-break-inside: avoid; }
        .signatures td { width: 50%; padding-right: 25px; vertical-align: top; }
        .signature-line { height: 25px; border-bottom: 1px solid #475569; margin: 18px 0 3px; }
        .muted { color: #64748b; font-size: 8px; }
        .footer { position: fixed; left: 0; right: 0; bottom: -9mm; color: #71717a; font-size: 7.5px; text-align: right; }
        .page-break { page-break-before: always; }
        ul { margin: 4px 0 8px; padding-left: 17px; }
        li { margin-bottom: 3px; }
    </style>
</head>
<body>
    <div class="footer">Generic project template - complete and review before use or signature.</div>

    <div class="brand">
        @if($project->documentLogoDataUri())
            <img src="{{ $project->documentLogoDataUri() }}" class="logo">
        @else
            {{ $project->documentSetting('brand_name', 'Project document') }}
        @endif
    </div>
    <h1>{{ $template['label'] }}</h1>
    <div class="notice">This is a blank project-level template. It intentionally does not fill in any participant, parent, guardian, representative or signatory name. Complete the empty fields and review the wording for your organisation and applicable law before use.</div>

    <table class="project">
        <tr>
            <td><div class="label">Project</div><div class="value">{{ $project->name }}</div></td>
            <td><div class="label">Grant reference</div><div class="value">{{ $project->grant_ref ?: '____________________________' }}</div></td>
        </tr>
        <tr>
            <td><div class="label">Project period</div><div class="value">{{ $project->start_date?->format('d M Y') ?: '____________' }} - {{ $project->end_date?->format('d M Y') ?: '____________' }}</div></td>
            <td><div class="label">Project code / acronym</div><div class="value">{{ $project->acronym ?: '____________________________' }}</div></td>
        </tr>
    </table>

    @if($templateKey === 'participant_agreement')
        <p>I, <span class="line"></span>, confirm that I will take part in the project activity described above and will provide the information needed for participation, travel, safeguarding, reporting and project administration.</p>
        <p>I understand the activity arrangements, safety instructions and code of conduct will be communicated before departure. I agree to follow the agreed rules, inform the organising team of relevant changes and return any project materials or documents requested after the activity.</p>
        <h2>Participant details to complete</h2>
        <div class="label">Full name</div><span class="line-wide"></span>
        <div class="label">Contact details</div><span class="line-wide"></span>
        <div class="label">Emergency contact</div><span class="line-wide"></span>
        <table class="signatures"><tr><td><div class="signature-line"></div><div class="muted">Participant signature and date</div></td><td><div class="signature-line"></div><div class="muted">Organisation representative signature and date</div></td></tr></table>
    @elseif($templateKey === 'parental_consent')
        <p>I, <span class="line"></span>, acting as parent or legal guardian of <span class="line"></span>, give consent for the child to participate in the project activity described above.</p>
        <p>I confirm that I have received the relevant information about the activity, travel, accommodation, supervision, emergency arrangements and contact points. I will inform the organising team of any information necessary for the child’s safety and participation.</p>
        <h2>Details to complete</h2>
        <div class="label">Child's full name and date of birth</div><span class="line-wide"></span>
        <div class="label">Parent / guardian contact details</div><span class="line-wide"></span>
        <div class="label">Relevant health, access or dietary information (if applicable)</div><span class="line-wide"></span>
        <table class="signatures"><tr><td><div class="signature-line"></div><div class="muted">Parent or legal guardian signature and date</div></td><td><div class="signature-line"></div><div class="muted">Organisation representative signature and date</div></td></tr></table>
    @elseif($templateKey === 'gdpr_declaration')
        <p>This form is a blank information and consent template. Before collecting any personal data, complete the controller details and ensure the chosen legal basis, purposes, recipients and retention period match the organisation’s actual processing.</p>
        <h2>Controller information to complete</h2>
        <div class="label">Controller organisation and contact details</div><span class="line-wide"></span>
        <div class="label">Data protection contact, if applicable</div><span class="line-wide"></span>
        <div class="label">Purposes, legal basis, recipients and retention period</div><div class="box"></div>
        <p><span class="check"></span>I confirm that I received clear information about the processing of my personal data and how to exercise my rights.</p>
        <p><span class="check"></span>Where consent is the chosen legal basis, I give consent for the specific purposes stated above and understand that it may be withdrawn as easily as it was given.</p>
        <div class="label">Data subject name (complete by hand)</div><span class="line-wide"></span>
        <table class="signatures"><tr><td><div class="signature-line"></div><div class="muted">Data subject signature and date</div></td><td><div class="signature-line"></div><div class="muted">Controller representative signature and date</div></td></tr></table>
    @endif
</body>
</html>
