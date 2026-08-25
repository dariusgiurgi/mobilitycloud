<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AccountAccess;
use App\Support\DemoWorkspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountAccessQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_scopes_match_the_account_access_rules_exactly(): void
    {
        $this->travelTo('2026-08-25 12:00:00');

        $users = collect([
            $this->user('active'),
            $this->user('plan-unlimited', ['plan' => 'unlimited']),
            $this->user('limit-unlimited', ['plan_limits' => ['unlimited' => true]]),
            $this->user('limit-false', ['plan_limits' => ['unlimited' => false]]),
            $this->user('flag-unlimited', ['feature_flags' => ['unlimited']]),
            $this->user('lookalike-flag', ['feature_flags' => ['not_unlimited']]),
            $this->user('nested-limit', ['plan_limits' => ['nested' => ['unlimited' => true]]]),
            $this->user('expired', ['subscription_status' => 'expired']),
            $this->user('past-end', ['subscription_ends_at' => now()->subSecond()]),
            $this->user('end-boundary', ['subscription_ends_at' => now()]),
            $this->user('future-end', ['subscription_ends_at' => now()->addDay()]),
            $this->user('suspended-flag', ['is_suspended' => true]),
            $this->user('suspended-status', ['subscription_status' => 'suspended']),
            $this->user('suspended-unlimited', ['plan' => 'unlimited', 'is_suspended' => true]),
            $this->user('expired-unlimited', ['plan' => 'unlimited', 'subscription_status' => 'expired']),
            $this->user('active-override', [
                'subscription_status' => 'expired',
                'access_override_reason' => 'Approved by owner',
                'access_override_ends_at' => now()->addHour(),
            ]),
            $this->user('permanent-override', [
                'is_suspended' => true,
                'access_override_reason' => 'Approved by owner',
                'access_override_ends_at' => null,
            ]),
            $this->user('expired-override', [
                'subscription_status' => 'expired',
                'access_override_reason' => 'Approved by owner',
                'access_override_ends_at' => now()->subSecond(),
            ]),
            $this->user('blank-override', [
                'subscription_status' => 'expired',
                'access_override_reason' => '   ',
                'access_override_ends_at' => now()->addHour(),
            ]),
            User::factory()->create([
                'email' => DemoWorkspace::VISITOR_EMAIL,
                'subscription_status' => 'active',
            ]),
        ]);

        $expectedUnlimited = $users
            ->filter(fn (User $user): bool => $user->isUnlimitedAccount())
            ->pluck('id')
            ->all();
        $expectedBillable = $users
            ->reject(fn (User $user): bool => $user->isUnlimitedAccount())
            ->pluck('id')
            ->all();
        $expectedReadOnly = $users
            ->filter(fn (User $user): bool => AccountAccess::isReadOnly($user))
            ->pluck('id')
            ->all();

        $this->assertEqualsCanonicalizing(
            $expectedUnlimited,
            User::query()->withUnlimitedAccess()->pluck('id')->all(),
        );
        $this->assertEqualsCanonicalizing(
            $expectedBillable,
            User::query()->withoutUnlimitedAccess()->pluck('id')->all(),
        );
        $this->assertEqualsCanonicalizing(
            $expectedReadOnly,
            User::query()->readOnlyAccounts()->pluck('id')->all(),
        );
    }

    private function user(string $key, array $attributes = []): User
    {
        return User::factory()->create([
            'email' => $key.'@example.test',
            'plan' => 'standard',
            'subscription_status' => 'active',
            'is_suspended' => false,
            ...$attributes,
        ]);
    }
}
