<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { font-size: 12px; color: #18181b; margin: 0; }
    h1 { font-size: 20px; margin: 0 0 2px; color: #1f2937; }
    .meta { font-size: 10px; color: #6b7280; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    td, th { padding: 8px 10px; border-bottom: 1px solid #e4e4e7; text-align: left; }
    th { background: #fafafa; font-size: 10px; text-transform: uppercase; color: #6b7280; }
    .right { text-align: right; }
    .cat { font-weight: bold; }
    .total td { font-weight: bold; font-size: 14px; background: #f4f4f5; border-top: 2px solid #18181b; }
    @include('pdf.partials.document-theme')
</style></head><body>

@include('pdf.partials.document-header', ['workspace' => $workspace, 'context' => 'Planning document', 'footerLeft' => 'Individual support estimate · Erasmus+ rates'])

<div class="mc-doc-title">
    <h1>Individual Support Calculation</h1>
    <div class="meta">
        {{ $workspace->name ?? '' }} · Generated {{ now()->format('d M Y, H:i') }}<br>
        {{ $participants }} participant(s) · {{ $days }}@if($isTravelInc) + {{ $travelDays }} travel @endif days
    </div>
</div>

<table>
    <thead>
        <tr><th>Category</th><th>Details</th><th class="right">Amount</th></tr>
    </thead>
    <tbody>
        <tr>
            <td class="cat">Individual Support</td>
            <td>{{ $participants }} × {{ $days + ($isTravelInc ? $travelDays : 0) }} days × &euro;{{ number_format($isRate, 2) }}</td>
            <td class="right">&euro; {{ number_format($isTotal, 2) }}</td>
        </tr>
        <tr>
            <td class="cat">Travel</td>
            <td>{{ $participants }} × &euro;{{ number_format($travelPer, 2) }} ({{ $bandLabel }}{{ $green ? ', green' : '' }})</td>
            <td class="right">&euro; {{ number_format($travelTotal, 2) }}</td>
        </tr>
        @if($includeOS)
        <tr>
            <td class="cat">Organisational Support</td>
            <td>{{ $participants }} × &euro;{{ number_format($osRate, 2) }}</td>
            <td class="right">&euro; {{ number_format($osTotal, 2) }}</td>
        </tr>
        @endif
        <tr class="total">
            <td colspan="2">TOTAL</td>
            <td class="right">&euro; {{ number_format($grand, 2) }}</td>
        </tr>
    </tbody>
</table>

</body></html>
