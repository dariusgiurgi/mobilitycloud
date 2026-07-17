<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkspaceInvitation;
use App\Support\PlanCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class AccountProjectInvitationArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_invitation_acceptance_adds_shared_project_without_consuming_invited_account_limit(): void
    {
        $owner = User::factory()->create([
            'name' => 'Owner Account',
            ...PlanCatalog::accountDefaults('writer_pro'),
        ]);
        $invited = User::factory()->create([
            'name' => 'Invited Account',
            ...PlanCatalog::accountDefaults('free'),
        ]);

        $project = Project::create([
            'owner_id' => $owner->id,
            'workspace_id' => null,
            'access_mode' => 'restricted',
            'name' => 'Shared Erasmus Project',
            'status' => 'writing',
        ]);

        $invitation = WorkspaceInvitation::create([
            'workspace_id' => null,
            'project_id' => $project->id,
            'invited_by' => $owner->id,
            'email' => $invited->email,
            'role' => 'project_editor',
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
        ]);

        $this->actingAs($invited)
            ->get(route('project-invitations.accept', $invitation->token))
            ->assertRedirect(ProjectResource::projectUrl($project, 'overview', $invited));

        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $invited->id,
            'role' => Project::PROJECT_ROLE_EDITOR,
        ]);

        $this->assertTrue(Project::query()->visibleToAccount($invited)->whereKey($project)->exists());
        Livewire::actingAs($invited)
            ->test(ListProjects::class)
            ->assertSee('Shared Erasmus Project')
            ->assertSee('Owner: Owner Account')
            ->assertSee('Editor');

        $this->assertTrue($invited->fresh()->can('create', Project::class));

        Project::create([
            'owner_id' => $invited->id,
            'workspace_id' => null,
            'access_mode' => 'restricted',
            'name' => 'Invited Own Project',
            'status' => 'writing',
        ]);

        $this->assertTrue($invited->fresh()->can('create', Project::class));
    }
}
