<?php

namespace App\Models;

use App\Support\PlanCatalog;
use Illuminate\Database\Eloquent\Model;

class PlatformPlan extends Model
{
    protected $fillable = [
        'key',
        'label',
        'description',
        'visibility',
        'recommended',
        'modules',
        'limits',
        'monthly_price',
        'yearly_price',
        'currency',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'recommended' => 'boolean',
        'modules' => 'array',
        'limits' => 'array',
        'monthly_price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public static function syncDefaultsFromCode(): void
    {
        foreach (PlanCatalog::codedPlans() as $index => $plan) {
            static::query()->firstOrCreate(
                ['key' => $index],
                [
                    'label' => $plan['label'],
                    'description' => $plan['description'] ?? null,
                    'visibility' => $plan['visibility'] ?? 'public',
                    'recommended' => (bool) ($plan['recommended'] ?? false),
                    'modules' => $plan['modules'] ?? [],
                    'limits' => $plan['limits'] ?? [],
                    'monthly_price' => $plan['monthly_price'] ?? null,
                    'yearly_price' => $plan['yearly_price'] ?? null,
                    'currency' => $plan['currency'] ?? 'EUR',
                    'sort_order' => array_search($index, array_keys(PlanCatalog::codedPlans()), true) ?: 0,
                    'is_active' => true,
                ],
            );
        }
    }
}
