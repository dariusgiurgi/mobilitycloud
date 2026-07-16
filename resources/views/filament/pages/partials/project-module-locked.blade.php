@props([
    'record',
    'module',
    'icon' => 'heroicon-o-lock-closed',
    'accent' => '#6366f1',
    'features' => [],
])

<x-filament::section>
    @php
        $isPaymentOverdue = $record->hasPaymentOverdue();
    @endphp
    <div style="display:grid;gap:1rem;max-width:860px;">
        <div style="display:flex;align-items:flex-start;gap:.85rem;">
            <div style="width:44px;height:44px;border-radius:14px;display:grid;place-items:center;background:color-mix(in srgb, {{ $accent }} 14%, transparent);color:{{ $accent }};flex:0 0 auto;">
                <x-filament::icon :icon="$isPaymentOverdue ? 'heroicon-o-exclamation-triangle' : $icon" style="width:22px;height:22px;" />
            </div>
            <div>
                <h2 class="text-gray-950 dark:text-white" style="font-size:1.05rem;font-weight:700;">
                    {{ $isPaymentOverdue ? $module.' is paused until payment is confirmed' : $module.' opens after project approval' }}
                </h2>
                <p class="text-gray-500 dark:text-gray-400" style="font-size:.84rem;line-height:1.55;margin-top:.3rem;">
                    @if ($isPaymentOverdue)
                        This project has an overdue activation invoice. Your data is safe, but implementation modules are unavailable until support confirms payment.
                    @else
                        This module is part of the implementation workspace. It becomes editable once the project is marked as approved, so writing stays focused on the application while the management tools are ready and visible.
                    @endif
                </p>
            </div>
        </div>

        @if (filled($features))
            <div style="display:grid;grid-template-columns:repeat({{ min(3, count($features)) }},minmax(0,1fr));gap:.75rem;">
                @foreach ($features as $feature)
                    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="padding:1rem;">
                        <p class="text-gray-950 dark:text-white" style="font-size:.9rem;font-weight:700;">{{ $feature['title'] ?? '' }}</p>
                        <p class="text-gray-500 dark:text-gray-400" style="font-size:.74rem;line-height:1.45;margin-top:.25rem;">{{ $feature['body'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
        @endif

        <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
            @if (! $isPaymentOverdue)
                <x-filament::button tag="a" :href="\App\Filament\Resources\Projects\ProjectResource::projectUrl($record, 'write')" icon="heroicon-o-document-text">
                    Continue application
                </x-filament::button>
            @endif
            <x-filament::button tag="a" :href="\App\Filament\Resources\Projects\ProjectResource::projectUrl($record)" color="gray" icon="heroicon-o-home">
                Project overview
            </x-filament::button>
            @if ($record->canBeManagedBy(auth()->user()))
                <x-filament::button tag="a" :href="\App\Filament\Resources\Projects\ProjectResource::projectUrl($record, 'edit')" color="gray" icon="heroicon-o-cog-6-tooth">
                    Project settings
                </x-filament::button>
            @endif
        </div>
    </div>
</x-filament::section>
