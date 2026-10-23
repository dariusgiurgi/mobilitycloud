<x-filament-panels::page>
    <x-filament::section heading="Global project fee">
        <p class="text-3xl font-bold">{{ app(\App\Services\ProjectPricing::class)->globalLabel() }}</p>
        <p class="mt-4">One payment per approved project, after the first grant instalment. Project writing is free.</p>
        <p class="mt-4">All accounts use the global fee by default. A change applies automatically to their future project approvals, including projects currently being drafted. Already agreed project fees remain unchanged.</p>
        <p class="mt-4">To negotiate a different tariff, open an account and select “Project fee”: choose the global fee, a custom percentage, or a fixed EUR amount per approved project. Unlimited accounts remain exempt.</p>
        <x-filament::link :href="\App\Filament\Resources\PlatformUsers\PlatformUserResource::getUrl('index')" class="mt-4">Manage account tariffs</x-filament::link>
    </x-filament::section>
</x-filament-panels::page>
