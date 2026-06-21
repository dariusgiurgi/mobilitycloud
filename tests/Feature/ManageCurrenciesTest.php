<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageCurrencies;
use App\Models\User;
use App\Models\Workspace;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManageCurrenciesTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_explains_rate_direction_and_application(): void
    {
        [$workspace, $user] = $this->workspaceAndUser();
        $workspace->update(['currencies' => ['RON' => 5.07]]);
        $this->actingAs($user);
        Filament::setTenant($workspace);

        Livewire::test(ManageCurrencies::class)
            ->assertSee('EUR is the base currency')
            ->assertSee('How to enter a rate')
            ->assertSee('When rates are applied')
            ->assertSee('1 RON = 0.197239 EUR')
            ->assertSee('Rates are entered manually');
    }

    public function test_manager_can_add_a_valid_currency_once(): void
    {
        [$workspace, $user] = $this->workspaceAndUser();
        $this->actingAs($user);
        Filament::setTenant($workspace);

        $component = Livewire::test(ManageCurrencies::class)
            ->set('newCode', 'ron')
            ->set('newRate', 5.07)
            ->call('addCurrency')
            ->assertHasNoErrors()
            ->assertSee('RON');

        $this->assertSame(5.07, $workspace->fresh()->currencies['RON']);

        $component
            ->set('newCode', 'RON')
            ->set('newRate', 5.1)
            ->call('addCurrency')
            ->assertHasErrors(['newCode']);

        $this->assertCount(1, $workspace->fresh()->currencies);
    }

    public function test_invalid_rate_does_not_replace_the_saved_value(): void
    {
        [$workspace, $user] = $this->workspaceAndUser();
        $workspace->update(['currencies' => ['RON' => 5.07]]);
        $this->actingAs($user);
        Filament::setTenant($workspace->fresh());

        Livewire::test(ManageCurrencies::class)
            ->call('updateRate', 0, 0)
            ->assertHasErrors(['rows.0.rate']);

        $this->assertSame(5.07, $workspace->fresh()->currencies['RON']);
    }

    public function test_viewer_can_read_rates_but_not_manage_them(): void
    {
        $workspace = Workspace::create([
            'name' => 'Viewer Currency Workspace',
            'currencies' => ['USD' => 1.08],
        ]);
        $viewer = User::factory()->create();
        $workspace->users()->attach($viewer, ['role' => 'viewer']);
        $this->actingAs($viewer);
        Filament::setTenant($workspace);

        Livewire::test(ManageCurrencies::class)
            ->assertSee('USD')
            ->assertDontSee('Add currency');
    }

    private function workspaceAndUser(): array
    {
        $workspace = Workspace::create(['name' => 'Currency Workspace']);
        $user = User::factory()->create();
        $workspace->users()->attach($user, ['role' => 'member']);

        return [$workspace, $user];
    }
}
