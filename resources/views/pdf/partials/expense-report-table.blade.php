<table class="expenses">
    <thead>
        <tr>
            <th style="width:3%;" class="center">No.</th>
            <th style="width:8%;">Reference</th>
            <th style="width:7%;">Date</th>
            <th style="width:14%;">Budget category</th>
            <th style="width:27%;">Description</th>
            <th style="width:9%;" class="num">Source amount</th>
            <th style="width:7%;" class="num">Rate</th>
            <th style="width:9%;" class="num">Amount EUR</th>
            <th style="width:16%;">Supporting evidence</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $index => $expense)
            <tr>
                <td class="center">{{ $expense['row_number'] ?? $index + 1 }}</td>
                <td>{{ $expense['reference'] }}</td>
                <td>{{ !empty($expense['date']) ? \Carbon\Carbon::parse($expense['date'])->format('d M Y') : '—' }}</td>
                <td>{{ $expense['budget_category'] }}</td>
                <td>{{ $expense['description'] ?: '—' }}@if(!empty($expense['notes']))<br><span style="color:#64748b;">{{ $expense['notes'] }}</span>@endif</td>
                <td class="num">{{ number_format((float) $expense['amount'], 2) }} {{ $expense['currency'] }}</td>
                <td class="num">{{ number_format((float) $expense['exchange_rate'], 6) }}</td>
                <td class="num"><strong>{{ number_format((float) $expense['amount_eur'], 2) }}</strong></td>
                <td class="{{ ($expense['evidence'] ?? '') === 'Attached' ? 'evidence-ok' : 'evidence-missing' }}">
                    {{ $expense['evidence'] ?? 'Missing' }}@if(!empty($expense['evidence_name']))<br><span style="color:#64748b;font-weight:normal;">{{ $expense['evidence_name'] }}</span>@endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
