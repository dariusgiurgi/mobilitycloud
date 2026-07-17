<?php

use App\Models\Project;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $unlimitedUserIds = DB::table('users')
            ->select(['id', 'plan', 'plan_limits', 'feature_flags'])
            ->get()
            ->filter(fn (object $user): bool => $this->isUnlimitedAccount($user))
            ->pluck('id')
            ->values();

        foreach ($unlimitedUserIds->chunk(200) as $ids) {
            DB::table('projects')
                ->whereIn('owner_id', $ids->all())
                ->update([
                    'activation_fee_amount' => 0,
                    'activation_fee_currency' => 'EUR',
                    'invoice_status' => Project::INVOICE_NOT_REQUIRED,
                    'invoice_number' => null,
                    'invoice_sent_at' => null,
                    'invoice_due_at' => null,
                    'payment_confirmed_at' => null,
                    'payment_confirmed_by' => null,
                    'updated_at' => now(),
                ]);

            DB::table('projects')
                ->whereIn('owner_id', $ids->all())
                ->where('status', 'payment_overdue')
                ->update([
                    'status' => 'approved',
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Data-only normalisation. Recreating historical invoices for unlimited
        // accounts would be incorrect because those accounts do not pay project
        // administration fees.
    }

    private function isUnlimitedAccount(object $user): bool
    {
        if (($user->plan ?? null) === 'unlimited') {
            return true;
        }

        $planLimits = $this->decodeJson($user->plan_limits ?? null);
        if (data_get($planLimits, 'unlimited') === true) {
            return true;
        }

        $featureFlags = $this->decodeJson($user->feature_flags ?? null);

        return is_array($featureFlags) && in_array('unlimited', $featureFlags, true);
    }

    private function decodeJson(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
};
