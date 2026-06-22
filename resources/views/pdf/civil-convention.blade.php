<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $type === 'copyright_assignment' ? 'Copyright Assignment Agreement' : 'Service Agreement' }}</title>
    <style>
        @page { margin: 18mm 17mm 17mm; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color:#18181b; font-size:9.2px; line-height:1.38; margin:0; }
        .header { border-bottom:1px solid #c7d2fe; padding-bottom:7px; margin-bottom:12px; }
        .brand { color:{{ $project->workspace->documentSetting('accent_color', '#4f46e5') }}; font-size:8px; font-weight:bold; text-transform:uppercase; letter-spacing:.08em; }
        .brand-logo { max-height:28px; max-width:125px; }
        h1 { font-size:17px; text-align:center; margin:12px 0 3px; letter-spacing:.03em; }
        .subtitle { text-align:center; color:#52525b; margin-bottom:13px; }
        h2 { font-size:10.5px; color:#312e81; margin:12px 0 5px; page-break-after:avoid; }
        p { margin:0 0 6px; text-align:justify; }
        ol, ul { margin:3px 0 7px 18px; padding:0; }
        li { margin-bottom:3px; }
        .parties { border:1px solid #cbd5e1; background:#f8fafc; padding:9px 11px; margin-bottom:10px; }
        .money { width:100%; border-collapse:collapse; margin:7px 0; }
        .money th, .money td { border:1px solid #cbd5e1; padding:5px 7px; }
        .money th { background:#eef2ff; color:#312e81; text-align:left; }
        .signatures { width:100%; border-collapse:collapse; margin-top:22px; page-break-inside:avoid; }
        .signatures td { width:50%; vertical-align:top; padding:7px 10px 40px; border-top:1px solid #a1a1aa; }
        .muted { color:#71717a; }
        .footer { position:fixed; bottom:-10mm; left:0; right:0; text-align:center; color:#71717a; font-size:7.5px; }
        .page-two { page-break-before:always; }
    </style>
</head>
<body>
<div class="footer">{{ $project->workspace->documentSetting('footer_text', 'Generated with MobilityCloud') }} - review before signature</div>
<div class="header">
    <div class="brand">@if($project->workspace->documentLogoDataUri())<img src="{{ $project->workspace->documentLogoDataUri() }}" class="brand-logo">@else{{ $project->workspace->documentSetting('brand_name', $data['beneficiary_name'] ?: $project->workspace->name) }}@endif</div>
    <div class="muted">{{ $project->name }}@if($project->grant_ref) · {{ $project->grant_ref }}@endif</div>
</div>

<h1>{{ $type === 'copyright_assignment' ? 'COPYRIGHT ASSIGNMENT AGREEMENT' : 'SERVICE AGREEMENT' }}</h1>
<div class="subtitle">No. {{ $data['convention_number'] }} · {{ \Carbon\Carbon::parse($data['contract_date'])->format('d F Y') }}@if($data['contract_place']) · {{ $data['contract_place'] }}@endif</div>

<h2>1. PARTIES</h2>
<div class="parties">
    <p><strong>Beneficiary:</strong> {{ $data['beneficiary_name'] ?: $project->workspace->name }}, registered under {{ $data['beneficiary_vat'] ?: 'N/A' }}, with registered office at {{ $data['beneficiary_address'] ?: 'N/A' }}, represented by {{ $data['beneficiary_representative'] ?: 'N/A' }}@if($data['beneficiary_representative_role']), {{ $data['beneficiary_representative_role'] }}@endif (the "Beneficiary").</p>
    <p><strong>{{ $type === 'copyright_assignment' ? 'Author' : 'Service Provider' }}:</strong> {{ $data['provider_name'] }}, {{ $data['provider_nationality'] ?: 'nationality not stated' }}, residing at {{ $data['provider_address'] }}, identified by {{ $data['provider_id_type'] ?: 'identity document' }} no. {{ $data['provider_id_number'] }}@if($data['provider_personal_number']), personal/tax no. {{ $data['provider_personal_number'] }}@endif (the "{{ $type === 'copyright_assignment' ? 'Author' : 'Provider' }}").</p>
</div>

@if($type === 'copyright_assignment')
    <h2>2. WORK AND ASSIGNMENT</h2>
    <p>The Author confirms authorship of the following original work (the "Work"): {{ $data['work_description'] }}</p>
    <p>The Author assigns to the Beneficiary, on an {{ !empty($data['rights_exclusive']) ? 'exclusive' : 'non-exclusive' }} basis, the following economic rights: {{ $data['rights_scope'] }}</p>

    <h2>3. METHODS, DURATION AND TERRITORY</h2>
    <p>The Beneficiary may use the Work through the following methods: {{ $data['use_methods'] }}</p>
    <p>The assignment applies for <strong>{{ $data['rights_duration'] }}</strong> and throughout <strong>{{ $data['rights_territory'] }}</strong>. @if(!empty($data['right_to_sublicense']))The Beneficiary may transfer or sublicense the assigned economic rights to third parties.@elseThe Beneficiary may not sublicense the assigned rights without the Author's prior written consent.@endif</p>
    <p>The Author's moral rights remain unaffected. The Author warrants that the Work is original and does not knowingly infringe third-party rights.</p>

    <h2>4. DELIVERY AND ACCEPTANCE</h2>
    <p>The Author shall deliver the Work in the agreed format. The Beneficiary may request reasonable corrections within 5 business days of delivery. In the absence of written objections during that period, the Work shall be deemed accepted.</p>
@else
    <h2>2. SUBJECT OF THE AGREEMENT</h2>
    <p>The Provider shall perform the following services independently and professionally for the Project: {{ $data['service_description'] }}</p>
    <p>The services shall be performed from {{ \Carbon\Carbon::parse($data['service_start_date'])->format('d F Y') }} to {{ \Carbon\Carbon::parse($data['service_end_date'])->format('d F Y') }}@if($data['service_location']), at {{ $data['service_location'] }}@endif.</p>

    <h2>3. OBLIGATIONS OF THE PROVIDER</h2>
    <ol>
        <li>Perform the services with reasonable skill, care and diligence and meet the agreed deadlines.</li>
        <li>Deliver any reports, materials or other outputs required by the service description.</li>
        <li>Notify the Beneficiary promptly of any issue that may affect delivery.</li>
        <li>Keep confidential all non-public Project and participant information.</li>
    </ol>

    <h2>4. OBLIGATIONS OF THE BENEFICIARY</h2>
    <ol>
        <li>Provide the information, access and reasonable cooperation needed to perform the services.</li>
        <li>Review the delivered services and communicate reasonable corrections within 5 business days.</li>
        <li>Pay the agreed fee under Section 6 after acceptance of the services.</li>
    </ol>

    <h2>5. ACCEPTANCE</h2>
    <p>The services are completed when the agreed outputs are delivered and accepted by the Beneficiary. If no written objections are submitted within 5 business days after delivery, the services shall be deemed accepted.</p>
@endif

<h2>{{ $type === 'copyright_assignment' ? '5' : '6' }}. FEE, TAX AND PAYMENT</h2>
<table class="money">
    <tr><th>Gross fee</th><td>{{ number_format($gross, 2) }} {{ $data['currency'] }}</td><th>Configured withholding</th><td>{{ number_format($taxRate, 2) }}%</td></tr>
    <tr><th>Estimated tax</th><td>{{ number_format($taxAmount, 2) }} {{ $data['currency'] }}</td><th>Estimated net payment</th><td>{{ number_format($netAmount, 2) }} {{ $data['currency'] }}</td></tr>
</table>
<p>The Beneficiary shall pay the amount due by bank transfer to {{ $data['provider_iban'] ?: 'the account notified by the provider' }}@if($data['provider_bank_name']), held with {{ $data['provider_bank_name'] }}@endif, within {{ $data['payment_due_days'] ?: 10 }} days after acceptance. Tax treatment and any withholding, declaration or payment obligations shall be determined under the law applicable on the payment date. The figures above reflect the rate configured for this agreement and must be verified before signature.</p>

<h2 class="page-two">{{ $type === 'copyright_assignment' ? '6' : '7' }}. INDEPENDENT STATUS</h2>
<p>This Agreement does not create an employment relationship, partnership or agency. The {{ $type === 'copyright_assignment' ? 'Author' : 'Provider' }} acts independently and is not subject to a working schedule or organisational subordination imposed by the Beneficiary.</p>

<h2>{{ $type === 'copyright_assignment' ? '7' : '8' }}. CONFIDENTIALITY AND DATA PROTECTION</h2>
<p>Each party shall keep confidential any non-public information received for the performance of this Agreement. Personal data shall be processed only as necessary for the Agreement and legal obligations, in accordance with Regulation (EU) 2016/679 and applicable national law.</p>

<h2>{{ $type === 'copyright_assignment' ? '8' : '9' }}. LIABILITY, FORCE MAJEURE AND TERMINATION</h2>
<p>Each party is liable for its own breach of this Agreement. Neither party is liable for failure caused by a duly established force majeure event. A material breach that is not remedied within a reasonable written cure period entitles the other party to terminate the Agreement.</p>

<h2>{{ $type === 'copyright_assignment' ? '9' : '10' }}. GOVERNING LAW AND FINAL PROVISIONS</h2>
<p>This Agreement is governed by Romanian law. The parties shall first attempt to resolve disputes amicably; unresolved disputes shall be submitted to the competent Romanian courts. Amendments must be made in writing and signed by both parties. The Agreement is executed in two counterparts, one for each party.</p>

<table class="signatures">
    <tr>
        <td><strong>BENEFICIARY</strong><br>{{ $data['beneficiary_name'] ?: $project->workspace->name }}<br><br>Name: {{ $data['beneficiary_representative'] ?: '________________' }}<br>Signature: ____________________</td>
        <td><strong>{{ $type === 'copyright_assignment' ? 'AUTHOR' : 'SERVICE PROVIDER' }}</strong><br>{{ $data['provider_name'] }}<br><br>Signature: ____________________</td>
    </tr>
</table>
</body>
</html>
