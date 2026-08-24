<x-filament-panels::page>
    <x-ui-polish />
    <style>
        .mc-cal{display:grid;gap:1rem}.mc-cal-toolbar{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}.mc-cal-toolbar-actions{display:flex;align-items:center;gap:.55rem;flex-wrap:wrap}.mc-cal-nav{display:flex;align-items:center;gap:.5rem}.mc-cal-grid{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));border:1px solid rgba(100,116,139,.18);border-radius:.8rem;overflow:hidden}.mc-cal-weekday{padding:.55rem;background:rgba(100,116,139,.06);color:#64748b;font-size:.65rem;font-weight:700;text-align:center;text-transform:uppercase}.mc-cal-day{min-height:118px;padding:.5rem;border-top:1px solid rgba(100,116,139,.14);border-right:1px solid rgba(100,116,139,.14);background:rgba(255,255,255,.02)}.mc-cal-day:nth-child(7n){border-right:0}.mc-cal-date{width:1.55rem;height:1.55rem;display:flex;align-items:center;justify-content:center;border-radius:9999px;font-size:.7rem;font-weight:650}.mc-cal-today{background:#4f46e5;color:white}.mc-cal-muted{opacity:.38}.mc-cal-event{display:block;width:100%;margin-top:.3rem;padding:.25rem .35rem;border:0;border-radius:.38rem;font-size:.61rem;line-height:1.25;overflow:hidden;text-align:left;text-overflow:ellipsis}.mc-cal-event-custom{cursor:pointer}.mc-event-project{background:#e0e7ff;color:#3730a3}.mc-event-mobility{background:#d1fae5;color:#065f46}.mc-event-task{background:#fef3c7;color:#92400e}.mc-event-overdue{background:#fee2e2;color:#991b1b}.mc-event-completed{background:#f1f5f9;color:#64748b;text-decoration:line-through}.mc-event-custom{background:#fae8ff;color:#86198f}.dark .mc-event-project{background:rgba(99,102,241,.2);color:#c7d2fe}.dark .mc-event-mobility{background:rgba(16,185,129,.18);color:#a7f3d0}.dark .mc-event-task{background:rgba(245,158,11,.18);color:#fde68a}.dark .mc-event-overdue{background:rgba(239,68,68,.18);color:#fecaca}.dark .mc-event-custom{background:rgba(192,38,211,.2);color:#f5d0fe}.mc-cal-modal-backdrop{position:fixed;z-index:50;inset:0;display:flex;align-items:center;justify-content:center;padding:1rem;background:rgba(15,23,42,.52)}.mc-cal-modal{width:min(100%,580px);padding:1.1rem;border-radius:.85rem;background:white;box-shadow:0 25px 70px rgba(15,23,42,.32)}.dark .mc-cal-modal{background:#1f2937}.mc-cal-modal-head{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem}.mc-cal-modal-head h3{font-size:1rem;font-weight:750}.mc-cal-modal-head p{margin-top:.18rem;color:#64748b;font-size:.72rem;line-height:1.4}.mc-cal-modal-fields{display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-top:1rem}.mc-cal-modal-fields label{display:grid;gap:.3rem;color:#475569;font-size:.7rem;font-weight:650}.mc-cal-modal-fields .mc-cal-modal-full{grid-column:1/-1}.mc-cal-modal-fields input,.mc-cal-modal-fields select,.mc-cal-modal-fields textarea{width:100%;padding:.55rem .6rem;border:1px solid rgba(100,116,139,.3);border-radius:.5rem;background:transparent;font:inherit;font-size:.78rem}.mc-cal-modal-fields textarea{min-height:88px;resize:vertical}.mc-cal-modal-actions{display:flex;align-items:center;justify-content:space-between;gap:.75rem;margin-top:1rem}.mc-cal-modal-actions>div{display:flex;gap:.55rem}@media(max-width:800px){.mc-cal-grid{display:none}.mc-cal-mobile{display:grid!important}.mc-cal-toolbar{align-items:flex-start}.mc-cal-day{min-height:auto}}@media(max-width:560px){.mc-cal-modal-fields{grid-template-columns:1fr}.mc-cal-modal{padding:1rem}.mc-cal-toolbar-actions{width:100%;justify-content:space-between}}
    </style>

    <div class="mc-cal">
        @php($canManageCustomDates = !\App\Support\PlatformAccess::isReadOnly())
        <div class="mc-cal-toolbar">
            <div class="mc-cal-nav">
                <x-filament::icon-button wire:click="previousMonth" icon="heroicon-o-chevron-left" color="gray" label="Previous month" />
                <h2 class="text-gray-950 dark:text-white" style="min-width:150px;text-align:center;font-size:1rem;font-weight:700;">{{ $this->currentMonth->format('F Y') }}</h2>
                <x-filament::icon-button wire:click="nextMonth" icon="heroicon-o-chevron-right" color="gray" label="Next month" />
                <x-filament::button wire:click="today" color="gray" size="sm">Today</x-filament::button>
            </div>
            <div class="mc-cal-toolbar-actions">
                <select wire:model.live="type" class="text-gray-950 dark:text-white" style="padding:.5rem .7rem;border:1px solid rgba(100,116,139,.3);border-radius:.55rem;background:transparent;font-size:.75rem;">
                    <option value="all">All events</option><option value="projects">Project dates</option><option value="mobility">Mobility dates</option><option value="tasks">Task deadlines</option><option value="custom">My custom dates</option>
                </select>
                @if($canManageCustomDates)<x-filament::button wire:click="openCreateEvent" icon="heroicon-o-plus" size="sm">Add custom date</x-filament::button>@endif
            </div>
        </div>

        <div class="mc-cal-grid">
            @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $weekday)<div class="mc-cal-weekday">{{ $weekday }}</div>@endforeach
            @foreach($this->calendarDays as $day)
                <div class="mc-cal-day {{ $day['current_month'] ? '' : 'mc-cal-muted' }}">
                    <span class="mc-cal-date {{ $day['today'] ? 'mc-cal-today' : '' }}">{{ $day['date']->day }}</span>
                    @foreach($day['events']->take(4) as $event)
                        @if($event['custom'] && $canManageCustomDates)
                            <button type="button" wire:click="openEditEvent({{ $event['id'] }})" class="mc-cal-event mc-cal-event-custom mc-event-{{ $event['kind'] }}" title="{{ $event['project'] }} · {{ $event['title'] }}{{ $event['notes'] ? ' · '.$event['notes'] : '' }}"><strong>{{ $event['project'] }}</strong><br>{{ $event['title'] }}</button>
                        @else
                            <a href="{{ $event['url'] }}" class="mc-cal-event mc-event-{{ $event['kind'] }}" title="{{ $event['project'] }} · {{ $event['title'] }}"><strong>{{ $event['project'] }}</strong><br>{{ $event['title'] }}</a>
                        @endif
                    @endforeach
                    @if($day['events']->count() > 4)<div class="text-gray-500" style="font-size:.58rem;margin-top:.25rem;">+{{ $day['events']->count()-4 }} more</div>@endif
                </div>
            @endforeach
        </div>

        <div class="mc-cal-mobile" style="display:none;gap:.65rem;">
            @forelse($this->upcoming as $event)
                @if($event['custom'] && $canManageCustomDates)
                    <button type="button" wire:click="openEditEvent({{ $event['id'] }})" style="display:flex;width:100%;gap:.75rem;align-items:center;padding:.75rem;border:1px solid rgba(100,116,139,.16);border-radius:.65rem;background:transparent;text-align:left;cursor:pointer;">
                @else
                    <a href="{{ $event['url'] }}" style="display:flex;gap:.75rem;align-items:center;padding:.75rem;border:1px solid rgba(100,116,139,.16);border-radius:.65rem;">
                @endif
                    <div class="text-gray-500" style="width:45px;font-size:.66rem;text-align:center;"><strong style="display:block;font-size:.9rem;">{{ \Carbon\Carbon::parse($event['date'])->format('d') }}</strong>{{ \Carbon\Carbon::parse($event['date'])->format('M') }}</div>
                    <div><div class="text-gray-950 dark:text-white" style="font-size:.78rem;font-weight:650;">{{ $event['title'] }}</div><div class="text-gray-500" style="font-size:.68rem;">{{ $event['project'] }}</div></div>
                @if($event['custom'])</button>@else</a>@endif
            @empty
                <div class="text-gray-500" style="font-size:.78rem;">No events in the next 30 days.</div>
            @endforelse
        </div>
    </div>

    @if($showEventModal)
        <div class="mc-cal-modal-backdrop" wire:keydown.escape.window="closeEventModal">
            <section class="mc-cal-modal text-gray-950 dark:text-white" role="dialog" aria-modal="true" aria-labelledby="custom-date-heading">
                <div class="mc-cal-modal-head">
                    <div><h3 id="custom-date-heading">{{ $editingEventId ? 'Edit custom date' : 'Add a custom date' }}</h3><p>Keep a personal deadline, meeting or planning milestone alongside project dates.</p></div>
                    <x-filament::icon-button wire:click="closeEventModal" icon="heroicon-o-x-mark" color="gray" size="sm" label="Close" />
                </div>
                <div class="mc-cal-modal-fields">
                    <label class="mc-cal-modal-full">Title<input type="text" wire:model="eventTitle" maxlength="160" placeholder="e.g. Confirm travel arrangements" autofocus>@error('eventTitle')<span class="text-danger-600">{{ $message }}</span>@enderror</label>
                    <label>Date<input type="date" wire:model="eventDate">@error('eventDate')<span class="text-danger-600">{{ $message }}</span>@enderror</label>
                    <label>Related project <select wire:model="eventProjectId"><option value="">No project — personal planning</option>@foreach($this->eventProjectOptions as $projectId => $projectName)<option value="{{ $projectId }}">{{ $projectName }}</option>@endforeach</select>@error('eventProjectId')<span class="text-danger-600">{{ $message }}</span>@enderror</label>
                    <label class="mc-cal-modal-full">Notes <textarea wire:model="eventNotes" maxlength="2000" placeholder="Optional context, location or reminder details"></textarea>@error('eventNotes')<span class="text-danger-600">{{ $message }}</span>@enderror</label>
                </div>
                <div class="mc-cal-modal-actions">
                    <div>@if($editingEventId)<x-filament::button wire:click="deleteEvent" wire:confirm="Delete this custom date?" color="danger" size="sm" icon="heroicon-o-trash">Delete</x-filament::button>@endif</div>
                    <div><x-filament::button wire:click="closeEventModal" color="gray" size="sm">Cancel</x-filament::button><x-filament::button wire:click="saveEvent" wire:loading.attr="disabled" wire:target="saveEvent" size="sm">{{ $editingEventId ? 'Save changes' : 'Add date' }}</x-filament::button></div>
                </div>
            </section>
        </div>
    @endif
</x-filament-panels::page>
