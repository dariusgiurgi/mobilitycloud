<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Models\Expense;
use App\Models\Participant;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\User;
use App\Models\Workspace;
use App\Services\AccountWorkspaceService;
use App\Services\ProjectDuplicator;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectDuplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reusable_structure_is_copied_without_operational_records(): void
    {
        [$workspace, $user, $source] = $this->sourceProject();
        $this->actingAs($user);
        $source->applicationSections()->create([
            'title' => 'Project objectives',
            'content' => 'Reusable application answer.',
            'category' => 'Relevance',
            'sort_order' => 1,
        ]);
        $source->budgetLines()->where('title', 'Travel')->firstOrFail()->update(['allocated_budget' => 4500]);
        $customLine = $source->budgetLines()->create([
            'title' => 'Local preparation',
            'allocated_budget' => 900,
            'sort_order' => 8,
        ]);
        Expense::create([
            'budget_line_id' => $customLine->id,
            'description' => 'Venue deposit',
            'amount' => 300,
            'currency' => 'EUR',
            'exchange_rate' => 1,
            'amount_eur' => 300,
        ]);
        Participant::create([
            'project_id' => $source->id,
            'first_name' => 'Ana',
            'last_name' => 'Pop',
            'role' => 'participant',
        ]);
        ProjectDocument::create([
            'project_id' => $source->id,
            'type' => ProjectDocument::TYPE_UPLOAD,
            'title' => 'Signed agreement',
        ]);

        $copy = app(ProjectDuplicator::class)->duplicate($source, [
            'name' => 'Youth Mobility Lab 2027',
            'copy_application' => true,
            'copy_budget' => true,
            'copy_partners' => true,
        ]);

        $this->assertSame($workspace->id, $copy->workspace_id);
        $this->assertSame('Youth Mobility Lab 2027', $copy->name);
        $this->assertSame('writing', $copy->status);
        $this->assertNull($copy->grant_ref);
        $this->assertNull($copy->approved_budget);
        $this->assertNull($copy->acronym);
        $this->assertNull($copy->start_date);
        $this->assertNull($copy->mobility_start_date);
        $this->assertSame($source->partner_orgs, $copy->partner_orgs);
        $this->assertSame($source->action_data, $copy->action_data);
        $this->assertSame('Reusable application answer.', $copy->applicationSections()->sole()->content);
        $this->assertSame('4500.00', $copy->budgetLines()->where('title', 'Travel')->sole()->allocated_budget);
        $this->assertSame('900.00', $copy->budgetLines()->where('title', 'Local preparation')->sole()->allocated_budget);
        $this->assertSame(0, $copy->participants()->count());
        $this->assertSame(0, $copy->documents()->count());
        $this->assertSame(0, $copy->budgetLines()->withCount('expenses')->get()->sum('expenses_count'));
    }

    public function test_optional_sections_can_be_excluded_from_the_copy(): void
    {
        [, $user, $source] = $this->sourceProject();
        $this->actingAs($user);
        $source->applicationSections()->create(['title' => 'Original answer', 'content' => 'Text']);

        $copy = app(ProjectDuplicator::class)->duplicate($source, [
            'name' => 'Blank reusable draft',
            'copy_application' => false,
            'copy_budget' => false,
            'copy_partners' => false,
        ]);

        $this->assertSame(0, $copy->applicationSections()->count());
        $this->assertSame([], $copy->partner_orgs);
        $this->assertNull($copy->action_data);
        $this->assertSame('0.00', $copy->total_budget);
        $this->assertTrue($copy->budgetLines()->get()->every(fn ($line): bool => (float) $line->allocated_budget === 0.0));
    }

    public function test_duplicate_action_uses_the_authenticated_users_own_project_limit(): void
    {
        [$workspace, $owner] = $this->workspaceUserAndProject('owner');
        $this->actingAs($owner);
        Filament::setTenant(app(AccountWorkspaceService::class)->ensureFor($owner));

        Livewire::test(ListProjects::class)->assertSee('Duplicate project');

        $member = User::factory()->create();
        $workspace->users()->attach($member, ['role' => 'member']);
        $this->actingAs($member);
        Filament::setTenant(app(AccountWorkspaceService::class)->ensureFor($member));

        Livewire::test(ListProjects::class)->assertSee('Duplicate project');

        $memberAccount = app(AccountWorkspaceService::class)->ensureFor($member);
        Project::create([
            'workspace_id' => $memberAccount->id,
            'name' => 'Member Existing Free Project',
            'status' => 'writing',
        ]);

        Livewire::test(ListProjects::class)->assertDontSee('Duplicate project');
    }

    public function test_duplicate_action_creates_the_draft_and_redirects_to_it(): void
    {
        [$workspace, $owner, $source] = $this->workspaceUserAndProject('owner');
        $this->actingAs($owner);
        Filament::setTenant(app(AccountWorkspaceService::class)->ensureFor($owner));

        Livewire::test(ListProjects::class)
            ->callAction('duplicateProject', data: [
                'source_id' => $source->id,
                'name' => 'Duplicated through action',
                'copy_application' => true,
                'copy_budget' => true,
                'copy_partners' => true,
            ]);

        $copy = Project::query()->where('name', 'Duplicated through action')->firstOrFail();
        $this->assertSame('writing', $copy->status);
        $this->assertSame($workspace->id, $copy->workspace_id);
    }

    public function test_duplicate_action_copies_shared_projects_into_the_users_own_account(): void
    {
        [$workspace, , $source] = $this->workspaceUserAndProject('owner');
        $collaborator = User::factory()->create();
        $source->members()->attach($collaborator, ['role' => Project::PROJECT_ROLE_EDITOR]);

        $this->actingAs($collaborator);
        Filament::setTenant(app(AccountWorkspaceService::class)->ensureFor($collaborator));

        Livewire::test(ListProjects::class)
            ->callAction('duplicateProject', data: [
                'source_id' => $source->id,
                'name' => 'Own copied draft',
                'copy_application' => true,
                'copy_budget' => true,
                'copy_partners' => true,
            ]);

        $copy = Project::query()->where('name', 'Own copied draft')->firstOrFail();
        $this->assertSame(app(AccountWorkspaceService::class)->ensureFor($collaborator)->id, $copy->workspace_id);
        $this->assertNotSame($workspace->id, $copy->workspace_id);
    }

    public function test_duplicate_button_can_mount_its_modal(): void
    {
        [$workspace, $owner] = $this->workspaceUserAndProject('owner');
        $this->actingAs($owner);
        Filament::setTenant(app(AccountWorkspaceService::class)->ensureFor($owner));

        Livewire::test(ListProjects::class)
            ->assertSeeHtml('wire:partial="action-modals"')
            ->mountAction('duplicateProject')
            ->assertActionMounted('duplicateProject');
    }

    private function sourceProject(): array
    {
        [$workspace, $user, $project] = $this->workspaceUserAndProject('member');
        $project->update([
            'acronym' => 'YML26',
            'grant_ref' => '2026-1-RO01-KA152-000001',
            'status' => 'active',
            'total_budget' => 15000,
            'approved_budget' => 14500,
            'start_date' => '2026-08-01',
            'mobility_start_date' => '2026-09-01',
            'partner_orgs' => [['name' => 'Partner Europe', 'country' => 'IT']],
            'action_data' => ['estimate' => ['total' => 15000]],
        ]);

        return [$workspace, $user, $project];
    }

    private function workspaceUserAndProject(string $role): array
    {
        $workspace = Workspace::create([
            'name' => 'Duplication Workspace',
            'plan_limits' => ['projects' => 5],
        ]);
        $user = User::factory()->create();
        $workspace->users()->attach($user, ['role' => $role]);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Youth Mobility Lab 2026',
            'status' => 'writing',
        ]);

        return [$workspace, $user, $project];
    }
}
