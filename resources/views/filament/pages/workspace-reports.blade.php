<x-filament-panels::page>
    <x-ui-polish />
    @php($report = $this->report)
    <style>
        .mc-reports{display:grid;gap:1rem}.mc-report-filters{display:grid;grid-template-columns:180px 160px 160px auto;gap:.65rem;align-items:end}.mc-report-field label{display:block;margin-bottom:.3rem;color:#64748b;font-size:.65rem;font-weight:700;text-transform:uppercase}.mc-report-field input,.mc-report-field select{width:100%;padding:.5rem .65rem;border:1px solid rgba(100,116,139,.28);border-radius:.5rem;background:transparent;font-size:.75rem}.mc-report-stats{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:.75rem}.mc-report-stat{padding:1rem;border:1px solid rgba(100,116,139,.14);border-radius:.7rem;background:rgba(255,255,255,.02)}.mc-report-stat span{display:block;color:#64748b;font-size:.65rem;text-transform:uppercase}.mc-report-stat strong{display:block;margin-top:.3rem;font-size:1.05rem}.mc-report-table{width:100%;border-collapse:collapse;font-size:.72rem}.mc-report-table th{text-align:left;padding:.55rem;color:#64748b;border-bottom:1px solid rgba(100,116,139,.2);font-size:.62rem;text-transform:uppercase}.mc-report-table td{padding:.65rem .55rem;border-bottom:1px solid rgba(100,116,139,.12)}.mc-report-num{text-align:right!important}@media(max-width:850px){.mc-report-filters{grid-template-columns:1fr 1fr}.mc-report-stats{grid-template-columns:1fr 1fr}.mc-report-table-wrap{overflow-x:auto}}@media(max-width:520px){.mc-report-filters{grid-template-columns:1fr}.mc-report-stats{grid-template-columns:1fr}}
    </style>

    <div class="mc-reports">
        <x-filament::section>
            <div class="mc-report-filters">
                <div class="mc-report-field"><label>Status</label><select wire:model.live="status" class="text-gray-950 dark:text-white"><option value="all">All statuses</option>@foreach(\App\Enums\ProjectStatus::cases() as $status)<option value="{{ $status->value }}">{{ $status->getLabel() }}</option>@endforeach</select></div>
                <div class="mc-report-field"><label>Expenses from</label><input type="date" wire:model.live="startDate" class="text-gray-950 dark:text-white"></div>
                <div class="mc-report-field"><label>Expenses until</label><input type="date" wire:model.live="endDate" class="text-gray-950 dark:text-white"></div>
                <div style="display:flex;gap:.5rem;"><x-filament::button tag="a" :href="$this->csvUrl" icon="heroicon-o-arrow-down-tray" size="sm">Export CSV</x-filament::button><x-filament::button wire:click="clearFilters" color="gray" size="sm">Clear</x-filament::button></div>
            </div>
        </x-filament::section>

        <div class="mc-report-stats">
            @foreach([
                ['Projects',$report['totals']['projects']],
                ['Funding','€ '.number_format($report['totals']['funding'],2)],
                ['Spent','€ '.number_format($report['totals']['spent'],2)],
                ['Participants',$report['totals']['participants']],
                ['Missing evidence',$report['totals']['missing_evidence']],
            ] as [$label,$value])<div class="mc-report-stat"><span>{{ $label }}</span><strong class="text-gray-950 dark:text-white">{{ $value }}</strong></div>@endforeach
        </div>

        <x-filament::section heading="Project portfolio" :description="$report['totals']['expenses'].' expense records in the selected period.'">
            <div class="mc-report-table-wrap"><table class="mc-report-table">
                <thead><tr><th>Project</th><th>Status</th><th class="mc-report-num">Funding</th><th class="mc-report-num">Spent</th><th class="mc-report-num">Remaining</th><th class="mc-report-num">Participants</th><th class="mc-report-num">Missing evidence</th></tr></thead>
                <tbody>@forelse($report['rows'] as $row)<tr><td><strong class="text-gray-950 dark:text-white">{{ $row['project'] }}</strong>@if($row['acronym'])<br><span class="text-gray-500">{{ $row['acronym'] }}</span>@endif</td><td>{{ ucfirst($row['status']) }}</td><td class="mc-report-num">€ {{ number_format($row['funding'],2) }}</td><td class="mc-report-num">€ {{ number_format($row['spent'],2) }}</td><td class="mc-report-num">€ {{ number_format($row['remaining'],2) }}</td><td class="mc-report-num">{{ $row['participants'] }}</td><td class="mc-report-num">{{ $row['missing_evidence'] }}</td></tr>@empty<tr><td colspan="7" class="text-gray-500" style="text-align:center;padding:2rem;">No accessible projects match these filters.</td></tr>@endforelse</tbody>
            </table></div>
        </x-filament::section>

        @if($report['categories']->isNotEmpty())
            <x-filament::section heading="Spending by budget category">
                <div style="display:grid;gap:.7rem;">@foreach($report['categories'] as $category)@php($pct=$category['allocated']>0?min(100,round($category['spent']/$category['allocated']*100)):0)<div><div style="display:flex;justify-content:space-between;font-size:.72rem;"><strong>{{ $category['category'] }}</strong><span>€ {{ number_format($category['spent'],2) }} / € {{ number_format($category['allocated'],2) }}</span></div><div style="height:6px;background:rgba(100,116,139,.14);border-radius:99px;margin-top:.3rem;overflow:hidden;"><div style="width:{{ $pct }}%;height:100%;background:#6366f1;"></div></div></div>@endforeach</div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
