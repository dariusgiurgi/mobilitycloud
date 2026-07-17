<?php

namespace Tests\Feature;

use App\Filament\Pages\IndividualSupportCalculator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IndividualSupportCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculator_explains_ambiguous_programme_terms(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        Livewire::test(IndividualSupportCalculator::class)
            ->assertSee('Planning estimate')
            ->assertSee('What this calculator includes')
            ->assertSee('Travel days with Individual Support')
            ->assertSee('Use the programme distance calculator')
            ->assertSee('Green travel')
            ->assertSee('Verify before using')
            ->assertSee('1,022.00');
    }

    public function test_calculation_breakdown_updates_with_inputs(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        Livewire::test(IndividualSupportCalculator::class)
            ->set('participants', 2)
            ->set('days', 5)
            ->set('isRate', 80)
            ->set('travelDays', 2)
            ->set('travelBandIndex', 2)
            ->set('osRate', 100)
            ->assertSee('2 people × 7 eligible days × 80.00 €')
            ->assertSee('1,742.00')
            ->set('isTravelDaysIncluded', false)
            ->assertSee('2 people × 5 eligible days × 80.00 €')
            ->assertSee('1,422.00');
    }

    public function test_calculator_tolerates_live_numeric_input_updates(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        Livewire::test(IndividualSupportCalculator::class)
            ->set('participants', '3')
            ->assertSee('3 people × 9 eligible days × 79.00 €')
            ->assertSee('3,066.00')
            ->set('participants', '')
            ->assertSee('1 people × 9 eligible days × 79.00 €');
    }

    private function user(): User
    {
        return User::factory()->create();
    }
}
