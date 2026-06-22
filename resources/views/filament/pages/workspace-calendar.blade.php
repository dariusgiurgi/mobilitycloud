<x-filament-panels::page>
    <x-ui-polish />
    <style>
        .mc-cal{display:grid;gap:1rem}.mc-cal-toolbar{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}.mc-cal-nav{display:flex;align-items:center;gap:.5rem}.mc-cal-grid{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));border:1px solid rgba(100,116,139,.18);border-radius:.8rem;overflow:hidden}.mc-cal-weekday{padding:.55rem;background:rgba(100,116,139,.06);color:#64748b;font-size:.65rem;font-weight:700;text-align:center;text-transform:uppercase}.mc-cal-day{min-height:118px;padding:.5rem;border-top:1px solid rgba(100,116,139,.14);border-right:1px solid rgba(100,116,139,.14);background:rgba(255,255,255,.02)}.mc-cal-day:nth-child(7n){border-right:0}.mc-cal-date{width:1.55rem;height:1.55rem;display:flex;align-items:center;justify-content:center;border-radius:9999px;font-size:.7rem;font-weight:650}.mc-cal-today{background:#4f46e5;color:white}.mc-cal-muted{opacity:.38}.mc-cal-event{display:block;margin-top:.3rem;padding:.25rem .35rem;border-radius:.38rem;font-size:.61rem;line-height:1.25;overflow:hidden;text-overflow:ellipsis}.mc-event-project{background:#e0e7ff;color:#3730a3}.mc-event-mobility{background:#d1fae5;color:#065f46}.mc-event-task{background:#fef3c7;color:#92400e}.mc-event-overdue{background:#fee2e2;color:#991b1b}.mc-event-completed{background:#f1f5f9;color:#64748b;text-decoration:line-through}.dark .mc-event-project{background:rgba(99,102,241,.2);color:#c7d2fe}.dark .mc-event-mobility{background:rgba(16,185,129,.18);color:#a7f3d0}.dark .mc-event-task{background:rgba(245,158,11,.18);color:#fde68a}.dark .mc-event-overdue{background:rgba(239,68,68,.18);color:#fecaca}@media(max-width:800px){.mc-cal-grid{display:none}.mc-cal-mobile{display:grid!important}.mc-cal-toolbar{align-items:flex-start}.mc-cal-day{min-height:auto}}
    </style>

    <div class="mc-cal">
        <div class="mc-cal-toolbar">
            <div class="mc-cal-nav">
                <x-filament::icon-button wire:click="previousMonth" icon="heroicon-o-chevron-left" color="gray" label="Previous month" />
                <h2 class="text-gray-950 dark:text-white" style="min-width:150px;text-align:center;font-size:1rem;font-weight:700;">{{ $this->currentMonth->format('F Y') }}</h2>
                <x-filament::icon-button wire:click="nextMonth" icon="heroicon-o-chevron-right" color="gray" label="Next month" />
                <x-filament::button wire:click="today" color="gray" size="sm">Today</x-filament::button>
            </div>
            <select wire:model.live="type" class="text-gray-950 dark:text-white" style="padding:.5rem .7rem;border:1px solid rgba(100,116,139,.3);border-radius:.55rem;background:transparent;font-size:.75rem;">
                <option value="all">All events</option><option value="projects">Project dates</option><option value="mobility">Mobility dates</option><option value="tasks">Task deadlines</option>
            </select>
        </div>

        <div class="mc-cal-grid">
            @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $weekday)<div class="mc-cal-weekday">{{ $weekday }}</div>@endforeach
            @foreach($this->calendarDays as $day)
                <div class="mc-cal-day {{ $day['current_month'] ? '' : 'mc-cal-muted' }}">
                    <span class="mc-cal-date {{ $day['today'] ? 'mc-cal-today' : '' }}">{{ $day['date']->day }}</span>
                    @foreach($day['events']->take(4) as $event)
                        <a href="{{ $event['url'] }}" class="mc-cal-event mc-event-{{ $event['kind'] }}" title="{{ $event['project'] }} · {{ $event['title'] }}"><strong>{{ $event['project'] }}</strong><br>{{ $event['title'] }}</a>
                    @endforeach
                    @if($day['events']->count() > 4)<div class="text-gray-500" style="font-size:.58rem;margin-top:.25rem;">+{{ $day['events']->count()-4 }} more</div>@endif
                </div>
            @endforeach
        </div>

        <div class="mc-cal-mobile" style="display:none;gap:.65rem;">
            @forelse($this->upcoming as $event)
                <a href="{{ $event['url'] }}" style="display:flex;gap:.75rem;align-items:center;padding:.75rem;border:1px solid rgba(100,116,139,.16);border-radius:.65rem;">
                    <div class="text-gray-500" style="width:45px;font-size:.66rem;text-align:center;"><strong style="display:block;font-size:.9rem;">{{ \Carbon\Carbon::parse($event['date'])->format('d') }}</strong>{{ \Carbon\Carbon::parse($event['date'])->format('M') }}</div>
                    <div><div class="text-gray-950 dark:text-white" style="font-size:.78rem;font-weight:650;">{{ $event['title'] }}</div><div class="text-gray-500" style="font-size:.68rem;">{{ $event['project'] }}</div></div>
                </a>
            @empty
                <div class="text-gray-500" style="font-size:.78rem;">No events in the next 30 days.</div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
