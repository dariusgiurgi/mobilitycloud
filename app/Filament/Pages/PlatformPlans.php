<?php

namespace App\Filament\Pages;

use App\Models\PlatformPlan;
use App\Models\User;
use App\Support\PlatformAudit;
use App\Support\PlanCatalog;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rule;
use Throwable;

class PlatformPlans extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|\UnitEnum|null $navigationGroup = 'Billing & access';

    protected static ?string $navigationLabel = 'Plans & entitlements';

    protected static ?int $navigationSort = 20;

    protected static ?string $title = 'Plans & entitlements';

    protected string $view = 'filament.pages.platform-plans';

    public bool $showPlanModal = false;

    public bool $syncExistingAccounts = false;

    public ?string $editingPlanKey = null;

    public array $form = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isPlatformAdmin() ?? false;
    }

    public function mount(): void
    {
        $this->syncDefaultPlans();
    }

    public function getSubheading(): ?string
    {
        return auth()->user()?->isPlatformOwner()
            ? 'Global plan catalogue. Changes are stored in the database and used by future account assignments.'
            : 'Read-only catalogue of plan modules and limits. Platform owners can edit global entitlements.';
    }

    public function canEditPlans(): bool
    {
        return auth()->user()?->isPlatformOwner() ?? false;
    }

    public function plans(): array
    {
        $modules = PlanCatalog::moduleOptions();

        return collect(PlanCatalog::plans())
            ->map(function (array $plan, string $key) use ($modules): array {
                return [
                    'key' => $key,
                    'label' => $plan['label'],
                    'description' => $plan['description'] ?? null,
                    'visibility' => $plan['visibility'] ?? 'public',
                    'recommended' => (bool) ($plan['recommended'] ?? false),
                    'monthly_price' => $plan['monthly_price'] ?? null,
                    'yearly_price' => $plan['yearly_price'] ?? null,
                    'currency' => $plan['currency'] ?? 'EUR',
                    'modules' => collect($plan['modules'] ?? [])
                        ->map(fn (string $module): array => [
                            'key' => $module,
                            'label' => $modules[$module] ?? str($module)->replace('_', ' ')->title(),
                        ])
                        ->values()
                        ->all(),
                    'limits' => $plan['limits'] ?? [],
                ];
            })
            ->all();
    }

    public function openCreatePlan(): void
    {
        abort_unless($this->canEditPlans(), 403);

        $this->editingPlanKey = null;
        $this->syncExistingAccounts = false;
        $this->form = [
            'key' => '',
            'label' => '',
            'description' => '',
            'visibility' => 'public',
            'recommended' => false,
            'monthly_price' => null,
            'yearly_price' => null,
            'currency' => 'EUR',
            'modules' => array_keys(PlanCatalog::moduleOptions()),
            'limits' => PlanCatalog::defaultLimits('free'),
            'is_active' => true,
        ];
        $this->showPlanModal = true;
    }

    public function openEditPlan(string $key): void
    {
        abort_unless($this->canEditPlans(), 403);

        $this->syncDefaultPlans();
        $plan = PlatformPlan::query()->where('key', $key)->firstOrFail();

        $this->editingPlanKey = $plan->key;
        $this->syncExistingAccounts = false;
        $this->form = [
            'key' => $plan->key,
            'label' => $plan->label,
            'description' => $plan->description,
            'visibility' => $plan->visibility,
            'recommended' => (bool) $plan->recommended,
            'monthly_price' => $plan->monthly_price === null ? null : (float) $plan->monthly_price,
            'yearly_price' => $plan->yearly_price === null ? null : (float) $plan->yearly_price,
            'currency' => $plan->currency ?: 'EUR',
            'modules' => $plan->modules ?: [],
            'limits' => [
                ...$this->emptyLimits(),
                ...($plan->limits ?: []),
            ],
            'is_active' => (bool) $plan->is_active,
        ];
        $this->showPlanModal = true;
    }

    public function closePlanModal(): void
    {
        $this->showPlanModal = false;
        $this->editingPlanKey = null;
        $this->syncExistingAccounts = false;
        $this->form = [];
    }

    public function savePlan(): void
    {
        abort_unless($this->canEditPlans(), 403);

        $moduleKeys = array_keys(PlanCatalog::moduleOptions());
        $this->form['key'] = str($this->form['key'] ?? '')->lower()->replace('-', '_')->slug('_')->toString();

        $this->validate([
            'form.key' => [
                'required',
                'string',
                'max:60',
                'regex:/^[a-z0-9_\\-]+$/',
                Rule::unique('platform_plans', 'key')->ignore($this->editingPlanKey, 'key'),
            ],
            'form.label' => ['required', 'string', 'max:120'],
            'form.description' => ['nullable', 'string', 'max:1000'],
            'form.visibility' => ['required', Rule::in(['public', 'internal'])],
            'form.recommended' => ['boolean'],
            'form.monthly_price' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'form.yearly_price' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'form.currency' => ['required', 'string', 'size:3'],
            'form.modules' => ['array'],
            'form.modules.*' => [Rule::in($moduleKeys)],
            'form.limits' => ['array'],
            'form.limits.*' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'form.is_active' => ['boolean'],
            'syncExistingAccounts' => ['boolean'],
        ]);

        $key = $this->form['key'];
        $limits = collect($this->emptyLimits())
            ->mapWithKeys(fn (int $default, string $limitKey): array => [
                $limitKey => max(0, (int) data_get($this->form, "limits.{$limitKey}", $default)),
            ])
            ->all();
        $modules = collect($this->form['modules'] ?? [])
            ->intersect($moduleKeys)
            ->values()
            ->all();

        $plan = PlatformPlan::query()->updateOrCreate(
            ['key' => $this->editingPlanKey ?: $key],
            [
                'key' => $key,
                'label' => trim((string) $this->form['label']),
                'description' => blank($this->form['description'] ?? null) ? null : trim((string) $this->form['description']),
                'visibility' => $this->form['visibility'],
                'recommended' => (bool) ($this->form['recommended'] ?? false),
                'monthly_price' => blank($this->form['monthly_price'] ?? null) ? null : (float) $this->form['monthly_price'],
                'yearly_price' => blank($this->form['yearly_price'] ?? null) ? null : (float) $this->form['yearly_price'],
                'currency' => strtoupper((string) $this->form['currency']),
                'modules' => $modules,
                'limits' => $limits,
                'is_active' => (bool) ($this->form['is_active'] ?? true),
                'sort_order' => PlatformPlan::query()->where('key', $key)->value('sort_order')
                    ?? PlatformPlan::query()->max('sort_order') + 1,
            ],
        );

        $syncedAccounts = 0;
        if ($this->syncExistingAccounts) {
            User::query()
                ->where('plan', $plan->key)
                ->each(function (User $user) use ($modules, $limits, &$syncedAccounts): void {
                    $user->forceFill([
                        'feature_flags' => $modules,
                        'plan_limits' => $limits,
                    ])->save();

                    $syncedAccounts++;
                });
        }

        PlatformAudit::log('platform_plan.updated', 'Updated platform plan '.$plan->label, $plan, [
            'plan' => $plan->key,
            'synced_accounts' => $syncedAccounts,
        ]);

        Notification::make()
            ->title('Plan saved')
            ->body($syncedAccounts > 0 ? $syncedAccounts.' existing accounts were synced.' : 'Global plan defaults were updated.')
            ->success()
            ->send();

        $this->closePlanModal();
    }

    public function resetPlanToCodeDefaults(string $key): void
    {
        abort_unless($this->canEditPlans(), 403);

        $defaults = PlanCatalog::codedPlans()[$key] ?? null;
        abort_unless($defaults !== null, 404);

        $plan = PlatformPlan::query()->updateOrCreate(
            ['key' => $key],
            [
                'label' => $defaults['label'],
                'description' => $defaults['description'] ?? null,
                'visibility' => $defaults['visibility'] ?? 'public',
                'recommended' => (bool) ($defaults['recommended'] ?? false),
                'monthly_price' => $defaults['monthly_price'] ?? null,
                'yearly_price' => $defaults['yearly_price'] ?? null,
                'currency' => $defaults['currency'] ?? 'EUR',
                'modules' => $defaults['modules'] ?? [],
                'limits' => $defaults['limits'] ?? [],
                'is_active' => true,
            ],
        );

        PlatformAudit::log('platform_plan.reset', 'Reset platform plan '.$plan->label.' to code defaults.', $plan);

        Notification::make()->title('Plan reset to defaults')->success()->send();
    }

    public function moduleOptions(): array
    {
        return PlanCatalog::moduleOptions();
    }

    public function limitOptions(): array
    {
        return [
            'projects' => 'Owned projects',
            'members' => 'Collaborators',
            'storage_mb' => 'Storage MB',
            'documents_per_month' => 'Documents / month',
            'ai_requests_per_month' => 'AI requests / month',
            'exports_per_month' => 'Exports / month',
        ];
    }

    public function limitLabel(string $key): string
    {
        return match ($key) {
            'storage_mb' => 'Storage',
            'documents_per_month' => 'Documents / month',
            'ai_requests_per_month' => 'AI requests / month',
            'exports_per_month' => 'Exports / month',
            default => str($key)->replace('_', ' ')->title(),
        };
    }

    public function limitValue(string $key, mixed $value): string
    {
        if ($key === 'storage_mb') {
            return ((int) $value) >= 1024
                ? number_format(((int) $value) / 1024).' GB'
                : number_format((int) $value).' MB';
        }

        return number_format((int) $value);
    }

    public function priceValue(?float $monthly, ?float $yearly, string $currency): string
    {
        if ($monthly === null && $yearly === null) {
            return 'Not priced';
        }

        return collect([
            $monthly !== null ? strtoupper($currency).' '.number_format($monthly, 2).' / month' : null,
            $yearly !== null ? strtoupper($currency).' '.number_format($yearly, 2).' / year' : null,
        ])->filter()->implode(' · ');
    }

    protected function syncDefaultPlans(): void
    {
        try {
            PlatformPlan::syncDefaultsFromCode();
        } catch (Throwable) {
            //
        }
    }

    protected function emptyLimits(): array
    {
        return collect($this->limitOptions())
            ->map(fn (): int => 0)
            ->all();
    }
}
