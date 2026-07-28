<?php

namespace Tests\Feature;

use App\Filament\Pages\AccountSettings;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectListTest extends TestCase
{
    use RefreshDatabase;

    public function test_projects_list_combines_owned_and_shared_projects(): void
    {
        $user = User::factory()->create();
        $owner = User::factory()->create(['name' => 'External Owner']);

        Project::create([
            'owner_id' => $user->id,
            'name' => 'Owned Project',
            'status' => 'writing',
        ]);

        $shared = Project::create([
            'owner_id' => $owner->id,
            'name' => 'Shared Project',
            'status' => 'active',
        ]);
        $shared->members()->attach($user, ['role' => Project::PROJECT_ROLE_EDITOR]);

        Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Hidden Project',
            'status' => 'active',
        ]);

        $this->actingAs($user);

        Livewire::test(ListProjects::class)
            ->assertSee('Owned Project')
            ->assertSee('Shared Project')
            ->assertDontSee('Hidden Project');
    }

    public function test_created_project_belongs_to_the_current_account(): void
    {
        $user = User::factory()->create([
            'billing_name' => 'Test NGO',
            'billing_country' => 'RO',
            'billing_address' => 'Main Street 1',
        ]);

        $this->actingAs($user);

        Livewire::test(CreateProject::class)
            ->fillForm([
                'name' => 'New Account Project',
                'status' => 'writing',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $project = Project::query()->where('name', 'New Account Project')->firstOrFail();

        $this->assertSame($user->id, $project->owner_id);
        $this->assertTrue($project->isOwnedBy($user));
    }

    public function test_standard_account_cannot_create_projects_with_placeholder_billing_details(): void
    {
        $user = User::factory()->create([
            'billing_name' => 'Contact Xeotype',
            'billing_country' => 'Romania',
            'billing_address' => 'To be completed',
            'plan' => 'standard',
            'subscription_status' => 'active',
        ]);

        $owner = User::factory()->create([
            'billing_name' => 'Owner NGO',
            'billing_country' => 'RO',
            'billing_address' => 'Main Street 1',
        ]);

        $shared = Project::create([
            'owner_id' => $owner->id,
            'name' => 'Shared test project',
            'status' => 'approved',
        ]);
        $shared->members()->attach($user, ['role' => Project::PROJECT_ROLE_EDITOR]);

        $this->actingAs($user);

        $this->assertFalse($user->can('create', Project::class));

        Livewire::test(ListProjects::class)
            ->assertSee('Complete your billing profile before creating a project')
            ->assertDontSee('New project');

        Livewire::test(CreateProject::class)
            ->assertRedirect(AccountSettings::getUrl());
    }

    public function test_unlimited_account_can_create_projects_without_billing_details(): void
    {
        $user = User::factory()->create([
            'billing_name' => null,
            'billing_country' => null,
            'billing_address' => null,
            'plan' => 'unlimited',
            'subscription_status' => 'active',
            'plan_limits' => ['unlimited' => true],
        ]);

        $this->actingAs($user);

        $this->assertTrue($user->can('create', Project::class));

        Livewire::test(CreateProject::class)
            ->fillForm([
                'name' => 'Unlimited Project',
                'status' => 'writing',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('projects', [
            'owner_id' => $user->id,
            'name' => 'Unlimited Project',
        ]);
    }
}
