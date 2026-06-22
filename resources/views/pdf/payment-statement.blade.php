<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment and Withholding Tax Statement</title>
    <style>
        @page { margin: 20mm 18mm 17mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 9px; line-height: 1.45; }
        .header { position: fixed; top: -13mm; left: 0; right: 0; height: 9mm; border-bottom: 1px solid #cbd5e1; }
        .brand { color: {{ $project->workspace->documentSetting('accent_color', '#4f46e5') }}; font-size: 11px; font-weight: bold; }
        .brand-logo { max-height: 28px; max-width: 125px; }
        .header-right { float: right; color: #64748b; font-size: 7px; padding-top: 2px; }
        .footer { position: fixed; bottom: -11mm; left: 0; right: 0; padding-top: 3px; border-top: 1px solid #e2e8f0; color: #64748b; font-size: 6.5px; }
        .page { float: right; }
        .page::after { content: "Page " counter(page); }
        h1 { margin: 0; color: #111827; font-size: 19px; }
        .subtitle { margin: 3px 0 14px; color: #64748b; font-size: 8px; }
        .status { float: right; margin-top: -35px; padding: 5px 10px; border-radius: 12px; background: #ecfdf5; color: #047857; font-size: 8px; font-weight: bold; text-transform: uppercase; }
        .section { margin-top: 13px; page-break-inside: avoid; }
        .section-title { margin-bottom: 6px; padding-bottom: 3px; border-bottom: 1px solid #cbd5e1; color: #334155; font-size: 8px; font-weight: bold; letter-spacing: .5px; text-transform: uppercase; }
        .details { width: 100%; border-collapse: separate; border-spacing: 6px 5px; margin: -5px -6px 0; }
        .details td { width: 50%; padding: 7px 8px; background: #f8fafc; border: 1px solid #e2e8f0; vertical-align: top; }
        .label { color: #64748b; font-size: 6.5px; font-weight: bold; letter-spacing: .35px; text-transform: uppercase; }
        .value { margin-top: 2px; color: #111827; font-size: 9px; font-weight: bold; }
        .calculation { width: 100%; border-collapse: collapse; }
        .calculation th { padding: 7px 8px; background: #334155; color: #fff; font-size: 7px; text-align: left; text-transform: uppercase; }
        .calculation td { padding: 8px; border: 1px solid #cbd5e1; }
        .calculation .amount { text-align: right; white-space: nowrap; }
        .calculation .net td { background: #eef2ff; border-top: 2px solid #4f46e5; color: #111827; font-size: 11px; font-weight: bold; }
        .statement { margin-top: 13px; padding: 9px 10px; background: #fff7ed; border: 1px solid #fed7aa; color: #7c2d12; page-break-inside: avoid; }
        .notes { margin-top: 7px; white-space: pre-line; }
        .signatures { width: 100%; margin-top: 24px; page-break-inside: avoid; }
        .signatures td { width: 50%; padding-right: 28px; vertical-align: top; }
        .signature-line { margin-top: 34px; padding-top: 4px; border-top: 1px solid #64748b; color: #64748b; font-size: 7px; }
    </style>
</head>
<body>
    <div class="header">
        <span class="brand">@if($project->workspace->documentLogoDataUri())<img src="{{ $project->workspace->documentLogoDataUri() }}" class="brand-logo">@else{{ $project->workspace->documentSetting('brand_name', $project->workspace->name) }}@endif</span>
        <span class="header-right">{{ $project->acronym ?: $project->name }} - Civil convention payment record</span>
    </div>
    <div class="footer">
        {{ $project->workspace->documentSetting('footer_text', 'Generated with MobilityCloud') }} · {{ now()->format('d M Y, H:i') }}
        <span class="page"></span>
    </div>

    <h1>Payment and Withholding Tax Statement</h1>
    <div class="subtitle">Supporting record for the payment made under a civil convention.</div>
    <div class="status">{{ $paymentStatus }}</div>

    <div class="section">
        <div class="section-title">Agreement and project</div>
        <table class="details">
            <tr>
                <td><div class="label">Project</div><div class="value">{{ $project->name }} @if($project->grant_ref) - {{ $project->grant_ref }} @endif</div></td>
                <td><div class="label">Agreement</div><div class="value">No. {{ $data['convention_number'] }} dated {{ \Carbon\Carbon::parse($data['contract_date'])->format('d M Y') }}</div></td>
                <td><div class="label">Payer / beneficiary</div><div class="value">{{ $data['beneficiary_name'] ?: $project->workspace?->name }}</div></td>
                <td><div class="label">Service provider</div><div class="value">{{ $data['provider_name'] }}</div></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Payment details</div>
        <table class="details">
            <tr>
                <td><div class="label">Payment date</div><div class="value">{{ \Carbon\Carbon::parse($data['payment_date'])->format('d M Y') }}</div></td>
                <td><div class="label">Payment method</div><div class="value">{{ $paymentMethod }}</div></td>
                <td><div class="label">Payment reference</div><div class="value">{{ $data['payment_reference'] ?: 'Not provided' }}</div></td>
                <td><div class="label">Provider account</div><div class="value">{{ $data['provider_iban'] ?: 'Not provided' }}@if(!empty($data['provider_bank_name'])) - {{ $data['provider_bank_name'] }} @endif</div></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Payment calculation</div>
        <table class="calculation">
            <thead><tr><th>Description</th><th style="width:28%;text-align:right;">Amount</th></tr></thead>
            <tbody>
                <tr><td>Gross contractual amount</td><td class="amount">{{ number_format($gross, 2) }} {{ $data['currency'] }}</td></tr>
                <tr><td>Withholding tax ({{ number_format($taxRate, 2) }}%)</td><td class="amount">- {{ number_format($taxAmount, 2) }} {{ $data['currency'] }}</td></tr>
                <tr class="net"><td>{{ ($data['payment_status'] ?? 'paid') === 'paid' ? 'Net amount paid' : 'Net amount payable' }}</td><td class="amount">{{ number_format($netAmount, 2) }} {{ $data['currency'] }}</td></tr>
            </tbody>
        </table>
    </div>

    <div class="statement">
        The payer records the gross contractual amount, the withholding calculated using the project tax rate configured at the date of generation, and the resulting net amount. This statement supports the project file and does not replace any tax declaration or statutory payment evidence required by the competent authorities.
        @if(!empty($data['payment_notes']))<div class="notes"><strong>Notes:</strong> {{ $data['payment_notes'] }}</div>@endif
    </div>

    <table class="signatures">
        <tr>
            <td>
                <strong>Prepared / approved by payer</strong><br>
                {{ $data['beneficiary_representative'] ?: $data['beneficiary_name'] }}@if(!empty($data['beneficiary_representative_role'])) - {{ $data['beneficiary_representative_role'] }} @endif
                <div class="signature-line">Name, date and signature</div>
            </td>
            <td>
                <strong>Payment received / acknowledged by provider</strong><br>
                {{ $data['provider_name'] }}
                <div class="signature-line">Name, date and signature</div>
            </td>
        </tr>
    </table>
</body>
</html>
