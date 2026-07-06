<?php

namespace App\Support;

use App\Models\User;

class AccountAccess
{
    public static function hasOwnerGrantedAccess(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return filled($user->access_override_reason)
            && (
                $user->access_override_ends_at === null
                || $user->access_override_ends_at->isFuture()
            );
    }

    public static function isSubscriptionActive(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if (self::hasOwnerGrantedAccess($user)) {
            return true;
        }

        if ($user->is_suspended || $user->subscription_status === 'suspended') {
            return false;
        }

        if ($user->plan === 'demo' || $user->subscription_status === 'demo') {
            return true;
        }

        if ($user->subscription_status === 'trial') {
            return ! $user->trial_ends_at || $user->trial_ends_at->endOfDay()->isFuture();
        }

        if ($user->subscription_status === 'expired') {
            return self::isInGracePeriod($user);
        }

        if ($user->subscription_ends_at && $user->subscription_ends_at->isPast()) {
            return self::isInGracePeriod($user);
        }

        return true;
    }

    public static function isInGracePeriod(?User $user): bool
    {
        if (! $user || ! $user->subscription_ends_at) {
            return false;
        }

        return $user->subscription_ends_at->copy()->addDays(7)->isFuture();
    }

    public static function isReadOnly(?User $user): bool
    {
        if (! $user || self::hasOwnerGrantedAccess($user)) {
            return false;
        }

        if ($user->is_suspended || $user->subscription_status === 'suspended') {
            return true;
        }

        if ($user->plan === 'demo' || $user->subscription_status === 'demo') {
            return false;
        }

        if ($user->subscription_status === 'expired') {
            return true;
        }

        return $user->subscription_ends_at?->isPast() ?? false;
    }

    public static function moduleEnabled(?User $user, string $module): bool
    {
        if (! $user || ! self::isSubscriptionActive($user)) {
            return false;
        }

        $enabled = $user->feature_flags;

        if (! is_array($enabled)) {
            $enabled = PlanCatalog::defaultModules((string) ($user->plan ?: 'free'));
        }

        return in_array($module, $enabled, true);
    }

    public static function limits(?User $user): array
    {
        if (! $user) {
            return [];
        }

        return [
            ...PlanCatalog::defaultLimits((string) ($user->plan ?: 'free')),
            ...($user->plan_limits ?: []),
        ];
    }

    public static function limit(?User $user, string $key): ?int
    {
        $value = self::limits($user)[$key] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }
}
