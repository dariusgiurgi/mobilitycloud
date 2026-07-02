<?php

namespace App\Filament\Resources\PlatformWorkspaces\Pages;

use App\Filament\Resources\PlatformWorkspaces\PlatformWorkspaceResource;
use App\Support\PlanCatalog;
use App\Support\PlatformAudit;
use App\Support\PlatformSubscriptionTimeline;
use Filament\Resources\Pages\EditRecord;

class EditPlatformWorkspace extends EditRecord
{
    protected static string $resource = PlatformWorkspaceResource::class;

    protected array $originalSubscriptionState = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $plan = (string) ($data['plan'] ?? 'free');
        $data['feature_flags'] ??= PlanCatalog::defaultModules($plan);
        $data['plan_limits'] = [
            ...PlanCatalog::defaultLimits($plan),
            ...($data['plan_limits'] ?? []),
        ];

        return $data;
    }

    public function getSubheading(): ?string
    {
        return $this->record->users()->count().' user(s) · '.$this->record->projects()->count().' project(s)';
    }

    protected function beforeSave(): void
    {
        $this->originalSubscriptionState = $this->record->only([
            'plan',
            'subscription_status',
            'trial_ends_at',
            'subscription_ends_at',
            'is_suspended',
            'suspension_category',
            'suspension_reason',
            'suspended_at',
            'feature_flags',
            'plan_limits',
            'billing_interval',
            'billing_amount',
            'billing_currency',
            'billing_reference',
            'billing_provider',
            'billing_provider_customer_id',
            'billing_provider_subscription_id',
            'demo_reset_frequency',
            'access_override_ends_at',
            'access_override_reason',
        ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (auth()->user()?->canManagePlatformAdmins() && filled($data['access_override_reason'] ?? null)) {
            $data['access_override_granted_by'] = auth()->id();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $changes = [];

        foreach ($this->originalSubscriptionState as $field => $oldValue) {
            $newValue = $this->record->{$field};

            if ($oldValue instanceof \DateTimeInterface) {
                $oldValue = $oldValue->format('Y-m-d H:i:s');
            }

            if ($newValue instanceof \DateTimeInterface) {
                $newValue = $newValue->format('Y-m-d H:i:s');
            }

            if (is_array($oldValue) || is_array($newValue)) {
                $oldComparable = json_encode($oldValue);
                $newComparable = json_encode($newValue);
            } else {
                $oldComparable = (string) $oldValue;
                $newComparable = (string) $newValue;
            }

            if ($oldComparable !== $newComparable) {
                $changes[$field] = [
                    'from' => $oldValue,
                    'to' => $newValue,
                ];
            }
        }

        if ($changes !== []) {
            $eventType = match (true) {
                array_key_exists('plan', $changes) => 'plan_changed',
                array_key_exists('subscription_status', $changes) => 'status_changed',
                count(array_intersect(array_keys($changes), [
                    'billing_interval',
                    'billing_amount',
                    'billing_currency',
                    'billing_reference',
                    'billing_provider',
                    'billing_provider_customer_id',
                    'billing_provider_subscription_id',
                ])) > 0 => 'billing_updated',
                array_key_exists('demo_reset_frequency', $changes) => 'demo_reset_configured',
                array_key_exists('access_override_reason', $changes) || array_key_exists('access_override_ends_at', $changes) => 'manual_note',
                ($changes['is_suspended']['to'] ?? null) === true || ($changes['is_suspended']['to'] ?? null) === '1' => 'suspended',
                ($changes['is_suspended']['from'] ?? null) === true || ($changes['is_suspended']['from'] ?? null) === '1' => 'reactivated',
                default => 'manual_note',
            };

            $labels = collect(array_keys($changes))
                ->map(fn (string $field): string => str($field)->replace('_', ' ')->title())
                ->join(', ');

            PlatformSubscriptionTimeline::record($this->record, $eventType, 'Updated subscription controls: '.$labels.'.', [
                'changes' => $changes,
            ]);
        }

        PlatformAudit::log('workspace.updated', 'Updated workspace '.$this->record->name, $this->record, [
            'changes' => $changes,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
