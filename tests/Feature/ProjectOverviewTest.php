<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\Pages\ViewProjectOverview;
use App\Models\Participant;
use App\Models\Project;
use App\Models\ProjectApplicationSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_overview_surfaces_the_next_step_and_module_readiness(): void
    {
        [$project, $user] = $this->workspaceProjectAndUser('member');
        ProjectApplicationSection::create([
            'project_id' => $project->id,
            'title' => 'Objectives',
            'content' => 'A complete answer.',
            'sort_order' => 1,
        ]);
        ProjectApplicationSection::create([
            'project_id' => $project->id,
            'title' => 'Impact',
            'content' => '',
            'sort_order' => 2,
        ]);
        Participant::create([
            'project_id' => $project->id,
            'first_name' => 'Ana',
            'last_name' => 'Popescu',
            'birth_date' => today()->subYears(20),
        ]);

        $this->actingAs($user);

        $component = Livewire::test(ViewProjectOverview::class, ['record' => $project->id])
            ->assertSee('Recommended next step')
            ->assertSee('Continue writing the application')
            ->assertSee('1/2 sections')
            ->assertSee('50% of sections contain text')
            ->assertSee('1 incomplete')
            ->assertSee('Grant estimate')
            ->assertSee('KA152-YOU - Youth Exchanges')
            ->assertSee('Project details')
            ->assertSee('Project stage')
            ->assertSee('Project readiness check')
            ->assertSee('Critical items need attention')
            ->assertSee('Application answers');

        $this->assertStringContainsString('/estimate', $component->instance()->getModuleUrls()['budget']);
        $this->assertArrayHasKey('groups', $component->instance()->getProjectReadiness());
    }

    public function test_manager_can_use_an_allowed_lifecycle_transition(): void
    {
        [$project, $user] = $this->workspaceProjectAndUser('member');
        $this->actingAs($user);

        Livewire::test(ViewProjectOverview::class, ['record' => $project->id])
            ->assertSee('Mark as Submitted')
            ->call('requestTransitionTo', 'submitted')
            ->assertSet('showTransitionReadinessModal', true)
            ->assertSee('Readiness warning before status change')
            ->assertSee('Continue anyway')
            ->call('confirmPendingTransition')
            ->assertSee('Awaiting the funding decision');

        $this->assertSame('submitted', $project->fresh()->status);
    }

    public function test_marking_a_project_as_approved_requires_and_locks_the_approved_grant(): void
    {
        [$project, $user] = $this->workspaceProjectAndUser('member');
        $this->actingAs($user);

        Livewire::test(ViewProjectOverview::class, ['record' => $project->id])
            ->assertSee('Mark as Approved')
            ->call('requestTransitionTo', 'approved')
            ->assertSet('showApprovalModal', true)
            ->assertSee('Declare approved grant')
            ->set('approvedGrantAmount', 25000)
            ->call('confirmApprovedGrant')
            ->assertSet('showApprovalModal', false)
            ->assertSee('Fiscal invoice pending')
            ->assertSee('250.00 €');

        $project->refresh();

        $this->assertSame('approved', $project->status);
        $this->assertSame('25000.00', $project->approved_grant_amount);
        $this->assertSame('25000.00', $project->approved_budget);
        $this->assertSame('250.00', $project->activation_fee_amount);
        $this->assertSame(Project::INVOICE_PENDING, $project->invoice_status);
        $this->assertNotNull($project->approved_declared_at);
        $this->assertNotNull($project->invoice_due_at);
        $this->assertTrue($project->implementationModulesAvailable());
    }

    public function test_approved_grant_fee_has_a_one_hundred_euro_minimum(): void
    {
        [$project, $user] = $this->workspaceProjectAndUser('member');
        $this->actingAs($user);

        Livewire::test(ViewProjectOverview::class, ['record' => $project->id])
            ->call('requestTransitionTo', 'approved')
            ->set('approvedGrantAmount', 4500)
            ->call('confirmApprovedGrant');

        $this->assertSame('100.00', $project->fresh()->activation_fee_amount);
    }

    public function test_unlimited_account_approval_has_no_administration_fee_or_invoice(): void
    {
        $user = User::factory()->create([
            'plan' => 'unlimited',
            'feature_flags' => ['unlimited'],
            'plan_limits' => ['unlimited' => true],
            'billing_name' => null,
            'billing_country' => null,
            'billing_address' => null,
        ]);

        $project = Project::create([
            'owner_id' => $user->id,
            'access_mode' => 'restricted',
            'name' => 'Unlimited Approved Project',
            'status' => 'writing',
            'ka_action' => 'ka152',
            'total_budget' => 15000,
        ]);

        $this->actingAs($user);

        Livewire::test(ViewProjectOverview::class, ['record' => $project->id])
            ->call('requestTransitionTo', 'approved')
            ->assertSet('showApprovalModal', true)
            ->set('approvedGrantAmount', 25000)
            ->assertSee('Included in unlimited account access')
            ->call('confirmApprovedGrant')
            ->assertDontSee('Fiscal invoice pending');

        $project->refresh();

        $this->assertSame('approved', $project->status);
        $this->assertSame('25000.00', $project->approved_grant_amount);
        $this->assertSame('0.00', $project->activation_fee_amount);
        $this->assertSame(Project::INVOICE_NOT_REQUIRED, $project->invoice_status);
        $this->assertNull($project->invoice_due_at);
        $this->assertTrue($project->implementationModulesAvailable());
    }

    public function test_paid_project_no_longer_shows_the_invoice_activation_notice(): void
    {
        [$project, $user] = $this->workspaceProjectAndUser('member');
        $project->update([
            'status' => 'approved',
            'approved_grant_amount' => 9900,
            'approved_budget' => 9900,
            'approved_declared_at' => now(),
            'activation_fee_amount' => 100,
            'invoice_status' => Project::INVOICE_PAID,
            'invoice_due_at' => now()->addDays(14),
        ]);

        $this->actingAs($user);

        Livewire::test(ViewProjectOverview::class, ['record' => $project->id])
            ->assertDontSee('Project activation')
            ->assertDontSee('Fiscal invoice pending')
            ->assertDontSee('Access paused until payment is confirmed')
            ->assertSee('Project stage');
    }

    public function test_readiness_transition_warning_can_be_cancelled(): void
    {
        [$project, $user] = $this->workspaceProjectAndUser('member');
        $this->actingAs($user);

        Livewire::test(ViewProjectOverview::class, ['record' => $project->id])
            ->call('requestTransitionTo', 'submitted')
            ->assertSet('showTransitionReadinessModal', true)
            ->call('closeTransitionReadinessModal')
            ->assertSet('showTransitionReadinessModal', false);

        $this->assertSame('writing', $project->fresh()->status);
    }

    public function test_viewer_does_not_see_project_mutation_actions(): void
    {
        [$project, $viewer] = $this->workspaceProjectAndUser('viewer');
        $this->actingAs($viewer);

        Livewire::test(ViewProjectOverview::class, ['record' => $project->id])
            ->assertSee('Continue writing the application')
            ->assertDontSee('Mark as Submitted')
            ->assertDontSee('Create tasks');
    }

    public function test_manager_can_create_tasks_from_readiness_issues_without_duplicates(): void
    {
        [$project, $user] = $this->workspaceProjectAndUser('member');
        ProjectApplicationSection::create([
            'project_id' => $project->id,
            'title' => 'Objectives',
            'content' => '',
            'sort_order' => 1,
        ]);

        $this->actingAs($user);

        $component = Livewire::test(ViewProjectOverview::class, ['record' => $project->id])
            ->assertSee('Create tasks')
            ->call('createTasksFromReadiness');

        $this->assertGreaterThan(0, $project->tasks()->where('status', 'open')->count());
        $this->assertTrue($project->tasks()->where('title', 'Resolve: Project dates')->exists());
        $taskCount = $project->tasks()->count();

        $component->call('createTasksFromReadiness');

        $this->assertSame($taskCount, $project->tasks()->count());
    }

    private function workspaceProjectAndUser(string $role): array
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $project = Project::create([
            'owner_id' => $owner->id,
            'name' => 'Youth Exchange',
            'status' => 'writing',
            'ka_action' => 'ka152',
            'total_budget' => 15000,
        ]);

        $project->members()->attach($user, [
            'role' => $role === 'viewer'
                ? Project::PROJECT_ROLE_VIEWER
                : Project::PROJECT_ROLE_EDITOR,
        ]);

        return [$project, $user];
    }
}
