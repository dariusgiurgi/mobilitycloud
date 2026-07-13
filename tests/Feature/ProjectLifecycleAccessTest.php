<?php

namespace Tests\Feature;

use App\Filament\Pages\IndividualSupportCalculator;
use App\Filament\Resources\Projects\Pages\ViewProjectBoard;
use App\Filament\Resources\Projects\Pages\ViewProjectDocuments;
use App\Filament\Resources\Projects\Pages\ViewProjectEstimate;
use App\Filament\Resources\Projects\Pages\ViewProjectMobility;
use App\Filament\Resources\Projects\Pages\ViewProjectParticipants;
use App\Filament\Resources\Projects\Pages\WriteApplication;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use App\Models\ProjectApplicationSection;
use App\Models\SavedCalculation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectLifecycleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_writing_stage_keeps_application_editable_and_management_modules_unavailable(): void
    {
        [$project, $owner] = $this->projectAndOwner('writing');
        $section = $this->section($project, 'Draft answer');

        $this->actingAs($owner);

        Livewire::test(WriteApplication::class, ['record' => $project->id])
            ->assertDontSee('Application locked')
            ->set("content.{$section->id}", 'Updated writing answer')
            ->assertSet("content.{$section->id}", 'Updated writing answer');

        $this->assertSame('Updated writing answer', $section->fresh()->content);

        Livewire::test(ViewProjectEstimate::class, ['record' => $project->id])
            ->assertSee('Grant estimator')
            ->set('persons', 8)
            ->assertSet('persons', 8);

        $this->assertSame(8, $project->fresh()->action_data['estimate']['inputs']['persons']);

        $this->get(ProjectResource::getUrl('board', ['record' => $project]))
            ->assertOk()
            ->assertSee('Budget opens after project approval');
        $this->get(ProjectResource::getUrl('participants', ['record' => $project]))->assertNotFound();
        $this->get(ProjectResource::getUrl('documents', ['record' => $project]))->assertNotFound();
        $this->get(ProjectResource::getUrl('mobility', ['record' => $project]))->assertNotFound();
    }

    public function test_approved_stage_locks_application_and_opens_management_modules(): void
    {
        [$project, $owner] = $this->projectAndOwner('approved');
        $section = $this->section($project, 'Approved answer');

        $this->actingAs($owner);

        Livewire::test(WriteApplication::class, ['record' => $project->id])
            ->assertSee('Application locked')
            ->assertSet("content.{$section->id}", 'Approved answer');

        Livewire::test(WriteApplication::class, ['record' => $project->id])
            ->call('setReviewStatus', $section->id, 'ready');

        Livewire::test(WriteApplication::class, ['record' => $project->id])
            ->set("content.{$section->id}", 'Should not persist');

        $this->assertSame('Approved answer', $section->fresh()->content);
        $this->assertSame('draft', $section->fresh()->review_status);
    }

    public function test_approved_management_pages_are_available_to_project_members(): void
    {
        [$project, $owner] = $this->projectAndOwner('approved');

        $this->actingAs($owner);

        Livewire::test(ViewProjectBoard::class, ['record' => $project->id])
            ->assertSee('Budget control');

        Livewire::test(ViewProjectParticipants::class, ['record' => $project->id])
            ->assertSee('Participant register');

        Livewire::test(ViewProjectDocuments::class, ['record' => $project->id])
            ->assertSee('Documents');

        Livewire::test(ViewProjectMobility::class, ['record' => $project->id])
            ->assertSee('Mobility');
    }

    public function test_global_individual_support_calculator_saves_scenarios_to_the_user_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(IndividualSupportCalculator::class)
            ->assertSee('Planning estimate')
            ->assertSee('Save calculation')
            ->call('openSave')
            ->set('saveName', 'KA152 planning')
            ->set('participants', 3)
            ->call('saveCalculation')
            ->assertSet('showSaveModal', false);

        $this->assertDatabaseHas('saved_calculations', [
            'workspace_id' => null,
            'created_by' => $user->id,
            'name' => 'KA152 planning',
            'type' => 'individual_support',
        ]);

        $calculation = SavedCalculation::query()->where('created_by', $user->id)->firstOrFail();

        Livewire::test(IndividualSupportCalculator::class)
            ->call('loadCalculation', $calculation->id)
            ->assertSet('participants', 3)
            ->call('deleteCalculation', $calculation->id);

        $this->assertDatabaseMissing('saved_calculations', [
            'id' => $calculation->id,
        ]);
    }

    private function projectAndOwner(string $status): array
    {
        $owner = User::factory()->create();

        $project = Project::create([
            'owner_id' => $owner->id,
            'workspace_id' => null,
            'access_mode' => 'restricted',
            'name' => 'Lifecycle Project',
            'status' => $status,
            'ka_action' => 'ka152',
            'total_budget' => 1000,
            'approved_budget' => 1000,
        ]);

        return [$project, $owner];
    }

    private function section(Project $project, string $content): ProjectApplicationSection
    {
        return ProjectApplicationSection::create([
            'project_id' => $project->id,
            'question_key' => 'objectives',
            'title' => 'What are the objectives?',
            'content' => $content,
            'char_limit' => 1000,
            'category' => 'Context',
            'sort_order' => 1,
        ]);
    }
}
