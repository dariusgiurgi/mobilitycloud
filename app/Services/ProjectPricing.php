<?php

namespace App\Services;

use App\Filament\Resources\PlatformUsers\PlatformUserResource;
use App\Models\Project;
use App\Models\User;
use App\Support\PlatformAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProjectPricing
{
    public function globalPercentage(): float
    {
        return (float) DB::table('project_fee_settings')->where('id', 1)->sole()->percentage;
    }

    public function globalLabel(): string
    {
        return $this->percentageLabel($this->globalPercentage());
    }

    private function percentageLabel(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.').'%';
    }

    public function forAccount(?User $owner): array
    {
        if ($owner?->isUnlimitedAccount()) {
            return ['mode' => 'fixed', 'value' => 0, 'source' => 'unlimited'];
        }

        $mode = $owner?->project_fee_mode ?? 'global';

        return [
            'mode' => $mode === 'fixed' ? 'fixed' : 'percentage',
            'value' => $mode === 'global' ? $this->globalPercentage() : (float) $owner->project_fee_value,
            'source' => $mode,
        ];
    }

    public function forProject(Project $project): array
    {
        if ($project->project_fee_snapshot !== null) {
            return $project->project_fee_snapshot;
        }

        // Preserve agreed fees on projects approved before configurable pricing.
        if ($project->hasDeclaredApprovedGrant() || $project->invoice_sent_at || $project->payment_confirmed_at) {
            return ['mode' => 'fixed', 'value' => (float) $project->activation_fee_amount, 'source' => 'legacy'];
        }

        return $this->forAccount($project->owner());
    }

    public function amount(array $pricing, float|int|string|null $grant): float
    {
        if ((float) $grant <= 0) {
            return 0;
        }

        return round($pricing['mode'] === 'fixed' ? (float) $pricing['value'] : (float) $grant * (float) $pricing['value'] / 100, 2);
    }

    public function label(array $pricing): string
    {
        if ($pricing['source'] === 'unlimited') {
            return 'Included in unlimited account access';
        }

        return $pricing['mode'] === 'fixed'
            ? number_format((float) $pricing['value'], 2, '.', ',').' EUR per project'
            : $this->percentageLabel((float) $pricing['value']).' of approved grant';
    }

    public function setGlobal(mixed $percentage): void
    {
        abort_unless(auth()->user()?->isPlatformAdmin(), 403);
        Validator::make(['percentage' => $percentage], ['percentage' => ['required', 'numeric', 'min:0.01', 'max:100', 'decimal:0,2']])->validate();

        DB::transaction(function () use ($percentage) {
            $previous = DB::table('project_fee_settings')->where('id', 1)->lockForUpdate()->sole()->percentage;
            DB::table('project_fee_settings')->where('id', 1)->update(['percentage' => $percentage]);
            PlatformAudit::log('pricing.global_updated', 'Updated global project fee', metadata: ['from' => $previous, 'to' => $percentage]);
        });
    }

    public function setAccount(User $account, array $data): void
    {
        abort_unless(PlatformUserResource::canManageAccount($account), 403);
        $mode = $data['mode'] ?? null;
        $validated = Validator::make($data, [
            'mode' => ['required', Rule::in(['global', 'percentage', 'fixed'])],
            'value' => $mode === 'global' ? ['exclude'] : ['required', 'numeric', 'min:0.01', 'max:'.($mode === 'percentage' ? '100' : '9999999999.99'), 'decimal:0,2'],
        ])->validate();

        DB::transaction(function () use ($account, $validated) {
            $locked = User::query()->lockForUpdate()->findOrFail($account->id);
            abort_unless(PlatformUserResource::canManageAccount($locked), 403);
            $previous = $locked->only('project_fee_mode', 'project_fee_value');
            $locked->forceFill(['project_fee_mode' => $validated['mode'], 'project_fee_value' => $validated['value'] ?? null])->save();
            PlatformAudit::log('pricing.account_updated', 'Updated project fee for '.$locked->email, $locked, ['from' => $previous, 'to' => $locked->only('project_fee_mode', 'project_fee_value')]);
        });
        $account->refresh();
    }
}
