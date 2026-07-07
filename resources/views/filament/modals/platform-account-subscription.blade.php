@php
    /** @var \App\Models\User $record */
    $enabledModules = $record->feature_flags ?: \App\Support\PlanCatalog::defaultModules((string) ($record->plan ?: 'free'));
    $latestEvents = $record->subscriptionEvents()->with('actor')->latest()->limit(5)->get();

    $badgeClasses = match ($accessState) {
        'Suspended', 'Expired / read-only' => 'bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-400/10 dark:text-danger-300 dark:ring-danger-400/30',
        'Manual access', 'Trial ending soon' => 'bg-warning-50 text-warning-700 ring-warning-600/20 dark:bg-warning-400/10 dark:text-warning-300 dark:ring-warning-400/30',
        default => 'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-400/10 dark:text-success-300 dark:ring-success-400/30',
    };
@endphp

<div class="space-y-5">
    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Account access</p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $badgeClasses }}">
                        {{ $accessState }}
                    </span>
                    <span class="text-sm text-gray-600 dark:text-gray-300">
                        {{ $planOptions[$record->plan] ?? ucfirst((string) $record->plan) }} · {{ $record->subscription_status ? str($record->subscription_status)->replace('_', ' ')->title() : 'Active' }}
                    </span>
                </div>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $record->email }}</p>
            </div>
            <div class="text-right text-sm text-gray-600 dark:text-gray-300">
                <div>{{ $record->owned_projects_count ?? $record->ownedProjects()->count() }} owned project(s)</div>
                <div>{{ $record->projects_count ?? $record->projects()->count() }} shared project(s)</div>
            </div>
        </div>
    </div>

    <div class="grid gap-3 md:grid-cols-3">
        <div class="rounded-xl border border-gray-200 p-3 dark:border-white/10">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Trial ends</p>
            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $record->trial_ends_at?->format('d M Y, H:i') ?? '—' }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 p-3 dark:border-white/10">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Subscription ends</p>
            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $record->subscription_ends_at?->format('d M Y, H:i') ?? '—' }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 p-3 dark:border-white/10">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Manual access ends</p>
            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $record->access_override_ends_at?->format('d M Y, H:i') ?? '—' }}</p>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 p-4 dark:border-white/10">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-gray-900 dark:text-white">Billing readiness</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Internal commercial metadata only. No payment is triggered from these values.</p>
            </div>
            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-white/10 dark:text-gray-200">
                {{ $record->billing_provider ? (\App\Filament\Resources\PlatformSubscriptions\PlatformSubscriptionResource::billingProviderOptions()[$record->billing_provider] ?? str($record->billing_provider)->replace('_', ' ')->title()) : 'Not connected' }}
            </span>
        </div>

        <div class="mt-3 grid gap-3 md:grid-cols-3">
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Interval</p>
                <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $record->billing_interval ? (\App\Filament\Resources\PlatformSubscriptions\PlatformSubscriptionResource::billingIntervalOptions()[$record->billing_interval] ?? str($record->billing_interval)->title()) : '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Amount</p>
                <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $record->billing_amount ? (($record->billing_currency ?: 'EUR').' '.number_format((float) $record->billing_amount, 2)) : '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Reference</p>
                <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $record->billing_reference ?: '—' }}</p>
            </div>
            <div class="md:col-span-3">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Provider IDs</p>
                <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                    Customer: {{ $record->billing_provider_customer_id ?: '—' }}
                    · Subscription: {{ $record->billing_provider_subscription_id ?: '—' }}
                </p>
            </div>
        </div>
    </div>

    @if ($record->is_suspended || $record->subscription_status === 'suspended')
        <div class="rounded-2xl border border-danger-200 bg-danger-50 p-4 dark:border-danger-400/30 dark:bg-danger-400/10">
            <p class="text-sm font-semibold text-danger-800 dark:text-danger-200">
                Suspended: {{ $suspensionCategoryOptions[$record->suspension_category] ?? 'No category' }}
            </p>
            <p class="mt-2 text-sm text-danger-700 dark:text-danger-200">{{ $record->suspension_reason ?: 'No suspension reason saved.' }}</p>
            <p class="mt-2 text-xs text-danger-700/80 dark:text-danger-200/80">
                {{ $record->suspended_at?->format('d M Y, H:i') ?? 'No timestamp' }}
                @if ($record->suspendedBy)
                    · by {{ $record->suspendedBy->name }}
                @endif
            </p>
        </div>
    @endif

    @if (filled($record->access_override_reason))
        <div class="rounded-2xl border border-warning-200 bg-warning-50 p-4 dark:border-warning-400/30 dark:bg-warning-400/10">
            <p class="text-sm font-semibold text-warning-800 dark:text-warning-200">Owner-granted manual access</p>
            <p class="mt-2 text-sm text-warning-700 dark:text-warning-200">{{ $record->access_override_reason }}</p>
            <p class="mt-2 text-xs text-warning-700/80 dark:text-warning-200/80">
                Granted by {{ $record->accessOverrideGrantor?->name ?? 'Unknown' }}
            </p>
        </div>
    @endif

    <div class="rounded-2xl border border-gray-200 p-4 dark:border-white/10">
        <p class="text-sm font-semibold text-gray-900 dark:text-white">Enabled modules</p>
        <div class="mt-3 flex flex-wrap gap-2">
            @forelse ($enabledModules as $module)
                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-white/10 dark:text-gray-200">
                    {{ $moduleOptions[$module] ?? $module }}
                </span>
            @empty
                <span class="text-sm text-gray-500 dark:text-gray-400">No modules enabled.</span>
            @endforelse
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 p-4 dark:border-white/10">
        <p class="text-sm font-semibold text-gray-900 dark:text-white">Latest subscription events</p>
        <div class="mt-3 divide-y divide-gray-100 dark:divide-white/10">
            @forelse ($latestEvents as $event)
                <div class="py-3 first:pt-0 last:pb-0">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $event->summary }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ \App\Models\PlatformSubscriptionEvent::typeOptions()[$event->event_type] ?? str($event->event_type)->replace('_', ' ')->title() }}
                                · {{ $event->actor?->name ?? 'System' }}
                            </p>
                        </div>
                        <p class="shrink-0 text-xs text-gray-500 dark:text-gray-400">{{ $event->created_at?->diffForHumans() }}</p>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">No timeline events yet.</p>
            @endforelse
        </div>
    </div>
</div>
