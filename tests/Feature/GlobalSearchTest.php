<?php

namespace Tests\Feature;

use App\Filament\Pages\GlobalSearch;
use App\Models\Participant;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\User;
use App\Models\Workspace;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_finds_multiple_record_types_without_leaking_restricted_projects(): void
    {
        $workspace = Workspace::create(['name' => 'Search Workspace']);
        $viewer = User::factory()->create();
        $workspace->users()->attach($viewer, ['role' => 'viewer']);
        $visible = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Aurora Exchange',
            'status' => 'active',
        ]);
        Participant::create([
            'project_id' => $visible->id,
            'first_name' => 'Aurora',
            'last_name' => 'Popescu',
            'email' => 'aurora@example.org',
        ]);
        ProjectDocument::create([
            'project_id' => $visible->id,
            'type' => ProjectDocument::TYPE_UPLOAD,
            'title' => 'Aurora mandate',
        ]);
        $hidden = Project::create([
            'workspace_id' => $workspace->id,
            'access_mode' => 'restricted',
            'name' => 'Aurora Confidential',
            'status' => 'active',
        ]);

        $this->actingAs($viewer);
        Filament::setTenant($workspace);

        Livewire::test(GlobalSearch::class)
            ->set('search', 'Aurora')
            ->assertSee('Aurora Exchange')
            ->assertSee('Aurora Popescu')
            ->assertSee('Aurora mandate')
            ->assertDontSee($hidden->name);
    }
}
