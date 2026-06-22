<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; box-sizing: border-box; }
    body { font-size: 11px; color: #18181b; margin: 0; }
    h1 { font-size: 20px; margin: 0 0 2px; color: #1f2937; }
    .muted { color: #6b7280; }
    .meta { font-size: 10px; color: #6b7280; }
    .summary { width: 100%; margin-bottom: 18px; border-collapse: collapse; }
    .summary td { width: 33.33%; border: 1px solid #e4e4e7; padding: 10px 12px; }
    .summary .label { font-size: 9px; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; }
    .summary .value { font-size: 15px; font-weight: bold; padding-top: 3px; }
    .basket { margin-bottom: 14px; page-break-inside: avoid; }
    .basket-head { border-left: 4px solid #6366f1; padding: 6px 10px; background: #f4f4f5; }
    .basket-head .title { font-weight: bold; font-size: 12px; }
    .basket-head .stats { font-size: 10px; color: #6b7280; margin-top: 2px; }
    table.exp { width: 100%; border-collapse: collapse; font-size: 10px; margin-top: 4px; }
    table.exp th { background: #fafafa; text-align: left; padding: 5px 8px; border-bottom: 1px solid #e4e4e7; font-size: 9px; text-transform: uppercase; color: #6b7280; }
    table.exp td { padding: 5px 8px; border-bottom: 1px solid #f4f4f5; }
    .right { text-align: right; }
    .center { text-align: center; }
    .code { font-family: DejaVu Sans Mono, monospace; color: #6b7280; }
    .subtotal td { font-weight: bold; background: #fafafa; padding: 5px 8px; }
    .empty { padding: 8px 10px; font-style: italic; color: #9ca3af; font-size: 10px; }
    .pos { color: #16a34a; } .neg { color: #dc2626; }
    @include('pdf.partials.document-theme')
</style>
</head>
<body>

@include('pdf.partials.document-header', ['workspace' => $project->workspace, 'context' => 'Financial overview', 'footerLeft' => $project->name.' · '.($project->workspace->documentSetting('footer_text', 'Generated with MobilityCloud'))])

<div class="mc-doc-title">
    <h1>Expense Report</h1>
    <div class="meta">
        <strong>{{ $project->name }}</strong>@if($project->acronym) · {{ $project->acronym }}@endif
        @if($project->grant_ref) · {{ $project->grant_ref }}@endif
        <br>
        {{ $project->workspace->name ?? '' }} · Generated {{ now()->format('d M Y, H:i') }}
    </div>
</div>

<table class="summary">
    <tr>
        <td>
            <div class="label">Total Budget</div>
            <div class="value">&euro; {{ number_format($totalBudget, 2) }}</div>
        </td>
        <td>
            <div class="label">Spent (EUR)</div>
            <div class="value">&euro; {{ number_format($totalSpent, 2) }}</div>
        </td>
        <td>
            <div class="label">Remaining</div>
            <div class="value {{ $totalRemaining < 0 ? 'neg' : 'pos' }}">&euro; {{ number_format($totalRemaining, 2) }}</div>
        </td>
    </tr>
</table>

@php
    $prefix = $project->expense_prefix ?: 'EXP';
    $pad = (int) ($project->expense_pad_length ?: 3);
@endphp

@foreach($project->budgetLines as $line)
    @php
        $lineSpent = $line->expenses->sum('amount_eur');
        $lineAlloc = (float) $line->allocated_budget;
        $lineLeft  = $lineAlloc - $lineSpent;
        $color = $line->color ?: '#6366f1';
    @endphp
    <div class="basket">
        <div class="basket-head" style="border-left-color: {{ $color }};">
            <div class="title">{{ $line->title }}</div>
            <div class="stats">
                Budget: &euro; {{ number_format($lineAlloc, 2) }} ·
                Spent: &euro; {{ number_format($lineSpent, 2) }} ·
                Left: <span class="{{ $lineLeft < 0 ? 'neg' : 'pos' }}">&euro; {{ number_format($lineLeft, 2) }}</span>
            </div>
        </div>

        @if($line->expenses->count() > 0)
        <table class="exp">
            <thead>
                <tr>
                    <th style="width:70px;">Code</th>
                    <th>Description</th>
                    <th style="width:65px;">Date</th>
                    <th style="width:30px;" class="center">CC</th>
                    <th style="width:70px;" class="right">Amount</th>
                    <th style="width:40px;" class="center">Cur.</th>
                    <th style="width:70px;" class="right">EUR</th>
                </tr>
            </thead>
            <tbody>
                @foreach($line->expenses->sortBy('position') as $e)
                <tr>
                    <td class="code">#{{ $prefix }}-{{ str_pad($e->id, $pad, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ $e->description ?: '—' }}</td>
                    <td>{{ $e->expense_date ? \Carbon\Carbon::parse($e->expense_date)->format('d M Y') : '—' }}</td>
                    <td class="center">{{ $e->is_civil_convention ? 'Yes' : '' }}</td>
                    <td class="right">{{ number_format((float) $e->amount, 2) }}</td>
                    <td class="center">{{ $e->currency }}</td>
                    <td class="right">&euro; {{ number_format((float) $e->amount_eur, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="subtotal">
                    <td colspan="6" class="right">Subtotal (EUR)</td>
                    <td class="right">&euro; {{ number_format($lineSpent, 2) }}</td>
                </tr>
            </tfoot>
        </table>
        @else
        <div class="empty">No expenses.</div>
        @endif
    </div>
@endforeach

</body>
</html>
