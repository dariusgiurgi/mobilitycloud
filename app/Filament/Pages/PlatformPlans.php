<?php

namespace App\Filament\Pages;

use App\Support\PlanCatalog;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class PlatformPlans extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|\UnitEnum|null $navigationGroup = 'Billing & access';

    protected static ?string $navigationLabel = 'Plans & entitlements';

    protected static ?int $navigationSort = 20;

    protected static ?string $title = 'Plans & entitlements';

    protected string $view = 'filament.pages.platform-plans';

    public static function canAccess(): bool
    {
        return auth()->user()?->isPlatformAdmin() ?? false;
    }

    public function getSubheading(): ?string
    {
        return 'Read-only catalogue of plan modules and limits. Billing-provider sync can be layered on top later.';
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

    public function moduleOptions(): array
    {
        return PlanCatalog::moduleOptions();
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
}
