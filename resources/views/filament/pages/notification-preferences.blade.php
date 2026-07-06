<x-filament-panels::page>
    <x-ui-polish />
    <style>
        .mc-pref{max-width:700px;display:grid;gap:1rem}.mc-pref-row{display:flex;align-items:center;justify-content:space-between;gap:1.2rem;padding:.95rem 0;border-top:1px solid rgba(100,116,139,.14)}.mc-pref-row:first-child{border-top:0;padding-top:0}.mc-switch{width:2.7rem;height:1.5rem;appearance:none;border-radius:9999px;background:#cbd5e1;position:relative;cursor:pointer;transition:.18s}.mc-switch:after{content:"";position:absolute;width:1.1rem;height:1.1rem;left:.2rem;top:.2rem;border-radius:9999px;background:white;box-shadow:0 1px 3px rgba(15,23,42,.25);transition:.18s}.mc-switch:checked{background:#6366f1}.mc-switch:checked:after{transform:translateX(1.2rem)}
    </style>
    <div class="mc-pref">
        <x-filament::section heading="Task notifications" description="These preferences apply to your account across every project you can access. Email delivery can be added later without changing these choices." icon="heroicon-o-bell">
            @foreach([
                ['taskAssigned','New task assigned','When another collaborator assigns a task to you.'],
                ['taskDueSoon','Deadline approaching','One reminder when an open task is due within three days.'],
                ['taskOverdue','Task overdue','One alert after an assigned task passes its deadline.'],
            ] as [$model,$label,$detail])
                <label class="mc-pref-row">
                    <span>
                        <span class="text-gray-950 dark:text-white" style="display:block;font-size:.82rem;font-weight:650;">{{ $label }}</span>
                        <span class="text-gray-500 dark:text-gray-400" style="display:block;font-size:.71rem;margin-top:.18rem;">{{ $detail }}</span>
                    </span>
                    <input type="checkbox" wire:model="{{ $model }}" class="mc-switch" aria-label="{{ $label }}">
                </label>
            @endforeach
            <div style="margin-top:1rem;">
                <x-filament::button wire:click="save" wire:loading.attr="disabled" wire:target="save" icon="heroicon-o-check">Save preferences</x-filament::button>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
