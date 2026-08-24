<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\Pages\ViewProjectFinalisation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectFinalisationPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_finalisation_uses_compact_archive_groups(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'owner_id' => $user->id,
            'access_mode' => 'restricted',
            'name' => 'Youth Exchange',
            'status' => 'active',
        ]);

        $this->actingAs($user);

        Livewire::test(ViewProjectFinalisation::class, ['record' => $project->id])
            ->assertSee('Prepare the final project archive')
            ->assertSee('Project essentials')
            ->assertSee('Evidence & files')
            ->assertSee('records included')
            ->call('toggleArchiveCategory', 'application')
            ->assertHasNoErrors();
    }
}
