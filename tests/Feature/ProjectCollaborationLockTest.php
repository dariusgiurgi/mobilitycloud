<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\Pages\ViewProjectBoard;
use App\Filament\Resources\Projects\Pages\ViewProjectParticipants;
use App\Filament\Resources\Projects\Pages\WriteApplication;
use App\Models\Participant;
use App\Models\Project;
use App\Models\ProjectApplicationSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectCollaborationLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_row_cannot_be_changed_while_another_user_is_editing_it(): void
    {
        [$project, $owner, $editor] = $this->projectWithEditor('active');
        $basket = $project->budgetLines()->firstOrFail();
        $expense = $basket->expenses()->create([
            'description' => 'Original invoice',
            'amount' => 100,
            'currency' => 'EUR',
            'exchange_rate' => 1,
            'amount_eur' => 100,
            'position' => 0,
            'created_by' => $owner->id,
        ]);

        $this->actingAs($owner);
        Livewire::test(ViewProjectBoard::class, ['record' => $project->id])
            ->call('startExpenseEditing', $expense->id);

        $this->actingAs($editor);
        Livewire::test(ViewProjectBoard::class, ['record' => $project->id])
            ->call('updateExpense', $expense->id, 'description', 'Changed by another user')
            ->assertHasErrors(['project_lock']);

        $this->assertSame('Original invoice', $expense->fresh()->description);
    }

    public function test_writing_question_cannot_be_changed_while_another_user_is_editing_it(): void
    {
        [$project, $owner, $editor] = $this->projectWithEditor('writing');
        $section = ProjectApplicationSection::create([
            'project_id' => $project->id,
            'question_key' => 'objectives',
            'title' => 'Project objectives',
            'content' => 'Original answer',
            'char_limit' => 1000,
            'category' => 'Context',
            'sort_order' => 0,
        ]);

        $this->actingAs($owner);
        Livewire::test(WriteApplication::class, ['record' => $project->id])
            ->call('startWritingSectionEditing', $section->id);

        $this->actingAs($editor);
        Livewire::test(WriteApplication::class, ['record' => $project->id])
            ->set("content.{$section->id}", 'Changed by another user')
            ->assertHasErrors(['project_lock']);

        $this->assertSame('Original answer', $section->fresh()->content);
    }

    public function test_participant_record_cannot_be_saved_while_another_user_is_editing_it(): void
    {
        [$project, $owner, $editor] = $this->projectWithEditor('active');
        $participant = Participant::create([
            'project_id' => $project->id,
            'complete_name' => 'Ana Popescu',
            'first_name' => 'Ana',
            'last_name' => 'Popescu',
            'partner_organisation' => 'Scoala de Jocuri',
            'country' => 'RO',
            'email' => 'ana@example.test',
            'role' => 'participant',
        ]);

        $this->actingAs($owner);
        Livewire::test(ViewProjectParticipants::class, ['record' => $project->id])
            ->call('openEdit', $participant->id);

        $this->actingAs($editor);
        Livewire::test(ViewProjectParticipants::class, ['record' => $project->id])
            ->call('openEdit', $participant->id)
            ->set('data.complete_name', 'Ana Changed')
            ->call('save')
            ->assertHasErrors(['project_lock']);

        $this->assertSame('Ana Popescu', $participant->fresh()->complete_name);
    }

    private function projectWithEditor(string $status): array
    {
        $owner = User::factory()->create();
        $editor = User::factory()->create();

        $project = Project::create([
            'owner_id' => $owner->id,
            'access_mode' => 'restricted',
            'name' => 'Shared Project',
            'status' => $status,
            'ka_action' => 'ka152',
            'invoice_status' => Project::INVOICE_NOT_REQUIRED,
            'approved_budget' => $status === 'writing' ? 0 : 10000,
            'approved_grant_amount' => $status === 'writing' ? null : 10000,
            'approved_declared_at' => $status === 'writing' ? null : now(),
        ]);

        $project->members()->attach($editor, ['role' => Project::PROJECT_ROLE_EDITOR]);

        return [$project, $owner, $editor];
    }
}
