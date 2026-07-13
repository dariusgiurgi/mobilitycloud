<?php

use App\Support\PlanCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_plans', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('visibility')->default('public');
            $table->boolean('recommended')->default(false);
            $table->json('modules')->nullable();
            $table->json('limits')->nullable();
            $table->decimal('monthly_price', 12, 2)->nullable();
            $table->decimal('yearly_price', 12, 2)->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index('visibility');
        });

        foreach (PlanCatalog::codedPlans() as $index => $plan) {
            DB::table('platform_plans')->insert([
                'key' => $index,
                'label' => $plan['label'],
                'description' => $plan['description'] ?? null,
                'visibility' => $plan['visibility'] ?? 'public',
                'recommended' => (bool) ($plan['recommended'] ?? false),
                'modules' => json_encode($plan['modules'] ?? []),
                'limits' => json_encode($plan['limits'] ?? []),
                'monthly_price' => $plan['monthly_price'] ?? null,
                'yearly_price' => $plan['yearly_price'] ?? null,
                'currency' => $plan['currency'] ?? 'EUR',
                'sort_order' => array_search($index, array_keys(PlanCatalog::codedPlans()), true) ?: 0,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_plans');
    }
};
