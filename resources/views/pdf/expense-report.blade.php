<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $document->title }}</title>
    <style>
        @page { margin: 22mm 10mm 16mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 8px; line-height: 1.35; }
        .header { position: fixed; top: -15mm; left: 0; right: 0; height: 11mm; border-bottom: 1px solid #cbd5e1; }
        .header-brand { color: #4338ca; font-size: 11px; font-weight: bold; }
        .header-project { float: right; color: #64748b; font-size: 7px; padding-top: 2px; }
        .footer { position: fixed; bottom: -11mm; left: 0; right: 0; color: #64748b; font-size: 6.5px; border-top: 1px solid #e2e8f0; padding-top: 3px; }
        .footer-right { float: right; }
        .footer-right::after { content: "Page " counter(page); }
        h1 { margin: 0 0 3px; color: #111827; font-size: 18px; }
        .subtitle { color: #64748b; font-size: 8px; margin-bottom: 10px; }
        .meta { width: 100%; border-collapse: separate; border-spacing: 5px 0; margin: 0 -5px 10px; }
        .meta td { width: 25%; padding: 6px 7px; background: #f8fafc; border: 1px solid #e2e8f0; vertical-align: top; }
        .label { color: #64748b; font-size: 6px; font-weight: bold; letter-spacing: .45px; text-transform: uppercase; }
        .value { color: #111827; font-size: 8px; font-weight: bold; margin-top: 2px; }
        table.expenses { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.expenses thead { display: table-header-group; }
        table.expenses tr { page-break-inside: avoid; }
        table.expenses th { padding: 5px 4px; color: #fff; background: #334155; border: 1px solid #475569; font-size: 6.2px; text-align: left; text-transform: uppercase; }
        table.expenses td { padding: 4px; border: 1px solid #cbd5e1; vertical-align: top; overflow-wrap: break-word; }
        table.expenses tbody tr:nth-child(even) td { background: #f8fafc; }
        .num { text-align: right; white-space: nowrap; }
        .center { text-align: center; }
        .evidence-ok { color: #15803d; font-weight: bold; }
        .evidence-missing { color: #b45309; font-weight: bold; }
        .sort-note { margin: -4px 0 8px; color: #64748b; font-size: 6.8px; }
        .category-section { margin-top: 8px; }
        .category-section.first { margin-top: 0; }
        .category-heading { padding: 5px 7px; background: #e0e7ff; border-left: 3px solid #4f46e5; color: #312e81; font-size: 9px; font-weight: bold; }
        .category-subtotal { margin: 3px 0 8px; text-align: right; color: #334155; font-size: 7px; font-weight: bold; }
        .summary { width: 100%; margin-top: 10px; page-break-inside: avoid; }
        .summary td { vertical-align: top; }
        .totals { width: 52%; margin-left: auto; border-collapse: collapse; }
        .totals td { padding: 4px 6px; border-bottom: 1px solid #e2e8f0; }
        .totals .grand td { color: #111827; background: #eef2ff; border-top: 2px solid #4f46e5; font-size: 9px; font-weight: bold; }
        .declaration { margin-top: 12px; padding: 8px 9px; border: 1px solid #cbd5e1; background: #f8fafc; page-break-inside: avoid; }
        .notes { margin-top: 7px; white-space: pre-line; }
        .signatures { width: 100%; margin-top: 16px; page-break-inside: avoid; }
        .signatures td { width: 50%; padding-right: 30px; vertical-align: top; }
        .signature-line { margin-top: 25px; border-top: 1px solid #64748b; padding-top: 3px; color: #64748b; }
        .empty { padding: 25px; border: 1px solid #cbd5e1; color: #64748b; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <span class="header-brand">MobilityCloud</span>
        <span class="header-project">{{ $project->acronym ?: $project->name }} · Official financial record</span>
    </div>
    <div class="footer">
        Generated {{ $document->generated_at?->format('d M Y, H:i') }} · Review against supporting evidence before signature.
        <span class="footer-right"></span>
    </div>

    <h1>{{ $document->title }}</h1>
    <div class="subtitle">Detailed statement of project expenditure, expressed in EUR for reporting purposes.</div>

    <table class="meta">
        <tr>
            <td><div class="label">Project</div><div class="value">{{ $project->name }} @if($project->acronym) ({{ $project->acronym }}) @endif</div></td>
            <td><div class="label">Grant reference</div><div class="value">{{ $project->grant_ref ?: '—' }}</div></td>
            <td><div class="label">Reporting period</div><div class="value">{{ !empty($report['period_start']) ? \Carbon\Carbon::parse($report['period_start'])->format('d M Y') : 'Beginning' }} – {{ !empty($report['period_end']) ? \Carbon\Carbon::parse($report['period_end'])->format('d M Y') : 'Present' }}</div></td>
            <td><div class="label">Report date / place</div><div class="value">{{ $document->document_date?->format('d M Y') }}@if(!empty($report['place'])) · {{ $report['place'] }} @endif</div></td>
        </tr>
    </table>
    <div class="sort-note">Expense order: {{ $report['order_label'] ?? 'Recorded order' }}</div>

    @if(!empty($report['expenses']))
        @if(($report['order_by'] ?? 'date') === 'category')
            @foreach(collect($report['expenses'])->groupBy('budget_category') as $category => $rows)
                <div class="category-section {{ $loop->first ? 'first' : '' }}"
                     @if(!$loop->first && !empty($report['page_break_by_category'])) style="page-break-before:always;" @endif>
                    <div class="category-heading">{{ $category }} · {{ $rows->count() }} expense record(s)</div>
                    @include('pdf.partials.expense-report-table', ['rows' => $rows])
                    <div class="category-subtotal">Basket subtotal: {{ number_format((float) $rows->sum('amount_eur'), 2) }} EUR</div>
                </div>
            @endforeach
        @else
            @include('pdf.partials.expense-report-table', ['rows' => $report['expenses']])
        @endif
    @else
        <div class="empty">No expenses were recorded for the selected reporting period.</div>
    @endif

    <table class="summary">
        <tr>
            <td>
                <div class="label">Summary</div>
                <div style="margin-top:3px;"><strong>{{ (int) ($report['expense_count'] ?? 0) }}</strong> expense record(s) included in this immutable report snapshot.</div>
            </td>
            <td>
                <table class="totals">
                    @foreach(($report['category_totals'] ?? []) as $total)
                        <tr><td>{{ $total['category'] }}</td><td class="num">{{ number_format((float) $total['amount_eur'], 2) }} EUR</td></tr>
                    @endforeach
                    <tr class="grand"><td>Total reported expenditure</td><td class="num">{{ number_format((float) ($report['total_eur'] ?? 0), 2) }} EUR</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="declaration">
        <strong>Declaration</strong><br>
        I confirm that the expenditure listed above relates to the project, is supported by the project accounting records and available evidence, and has not knowingly been declared twice.
        @if($document->notes)<div class="notes"><strong>Notes:</strong> {{ $document->notes }}</div>@endif
    </div>

    <table class="signatures">
        <tr>
            <td>
                <strong>Prepared by</strong><br>
                {{ $report['prepared_by'] ?? '—' }}@if(!empty($report['prepared_by_role'])) · {{ $report['prepared_by_role'] }} @endif
                <div class="signature-line">Date and signature</div>
            </td>
            <td>
                <strong>Approved by</strong><br>
                {{ $project->workspace?->billing_name ?: $project->workspace?->name }}
                <div class="signature-line">Name, role, date and signature</div>
            </td>
        </tr>
    </table>

</body>
</html>
