<?php

namespace App\Support;

use App\Models\Workspace;

class WorkspaceAccess
{
    public static function hasOwnerGrantedAccess(?Workspace $workspace): bool
    {
        if (! $workspace) {
            return false;
        }

        return filled($workspace->access_override_reason)
            && (
                $workspace->access_override_ends_at === null
                || $workspace->access_override_ends_at->isFuture()
            );
    }

    public static function isSubscriptionActive(?Workspace $workspace): bool
    {
        if (! $workspace) {
            return false;
        }

        if (self::hasOwnerGrantedAccess($workspace)) {
            return true;
        }

        if ($workspace->is_suspended || $workspace->subscription_status === 'suspended') {
            return false;
        }

        if ($workspace->plan === 'demo' || $workspace->subscription_status === 'demo') {
            return true;
        }

        if ($workspace->subscription_status === 'trial') {
            return ! $workspace->trial_ends_at || $workspace->trial_ends_at->endOfDay()->isFuture();
        }

        if ($workspace->subscription_status === 'expired') {
            return self::isInGracePeriod($workspace);
        }

        if ($workspace->subscription_ends_at && $workspace->subscription_ends_at->isPast()) {
            return self::isInGracePeriod($workspace);
        }

        return true;
    }

    public static function isInGracePeriod(?Workspace $workspace): bool
    {
        if (! $workspace || ! $workspace->subscription_ends_at) {
            return false;
        }

        return $workspace->subscription_ends_at->copy()->addDays(7)->isFuture();
    }

    public static function isReadOnly(?Workspace $workspace): bool
    {
        if (! $workspace || self::hasOwnerGrantedAccess($workspace)) {
            return false;
        }

        if ($workspace->is_suspended || $workspace->subscription_status === 'suspended') {
            return true;
        }

        if ($workspace->plan === 'demo' || $workspace->subscription_status === 'demo') {
            return false;
        }

        if ($workspace->subscription_status === 'expired') {
            return true;
        }

        return $workspace->subscription_ends_at?->isPast() ?? false;
    }

    public static function moduleEnabled(?Workspace $workspace, string $module): bool
    {
        if (! $workspace || ! self::isSubscriptionActive($workspace)) {
            return false;
        }

        $enabled = $workspace->feature_flags;

        if (! is_array($enabled)) {
            $enabled = PlanCatalog::defaultModules((string) ($workspace->plan ?: 'free'));
        }

        return in_array($module, $enabled, true);
    }

    public static function limits(?Workspace $workspace): array
    {
        if (! $workspace) {
            return [];
        }

        return [
            ...PlanCatalog::defaultLimits((string) ($workspace->plan ?: 'free')),
            ...($workspace->plan_limits ?: []),
        ];
    }

    public static function limit(?Workspace $workspace, string $key): ?int
    {
        $value = self::limits($workspace)[$key] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }
}
