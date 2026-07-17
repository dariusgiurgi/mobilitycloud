<?php

namespace Tests\Feature;

use App\Filament\Pages\GlobalSearch;
use App\Models\Participant;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_finds_multiple_record_types_without_leaking_restricted_projects(): void
    {
        $viewer = User::factory()->create();
        $visible = Project::create([
            'owner_id' => $viewer->id,
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
        $otherUser = User::factory()->create();
        $hidden = Project::create([
            'owner_id' => $otherUser->id,
            'access_mode' => 'restricted',
            'name' => 'Aurora Confidential',
            'status' => 'active',
        ]);

        $this->actingAs($viewer);

        Livewire::test(GlobalSearch::class)
            ->set('search', 'Aurora')
            ->assertSee('Aurora Exchange')
            ->assertSee('Aurora Popescu')
            ->assertSee('Aurora mandate')
            ->assertDontSee($hidden->name);
    }

    public function test_search_uses_the_full_accessible_project_portfolio(): void
    {
        $user = User::factory()->create();
        $owner = User::factory()->create();
        $homeProject = Project::create([
            'owner_id' => $user->id,
            'name' => 'Home Aurora',
            'status' => 'active',
        ]);
        $sharedProject = Project::create([
            'owner_id' => $owner->id,
            'name' => 'Shared Aurora',
            'status' => 'active',
        ]);
        $sharedProject->members()->attach($user, ['role' => Project::PROJECT_ROLE_VIEWER]);

        Participant::create([
            'project_id' => $sharedProject->id,
            'first_name' => 'Aurora',
            'last_name' => 'Shared',
        ]);

        $this->actingAs($user);

        Livewire::test(GlobalSearch::class)
            ->set('search', 'Aurora')
            ->assertSee($homeProject->name)
            ->assertSee($sharedProject->name)
            ->assertSee('Aurora Shared');
    }
}
