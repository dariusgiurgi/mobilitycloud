<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\User;
use App\Models\Workspace;
use App\Services\ExpenseReportSnapshot;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectDocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_member_can_download_landscape_attendance_pdf(): void
    {
        [$workspace, $project] = $this->workspaceAndProject();
        $user = User::factory()->create();
        $workspace->users()->attach($user, ['role' => 'member']);
        $this->participants($project);
        $document = $this->attendanceDocument($project);

        $response = $this->actingAs($user)
            ->get(route('project-documents.attendance', [$project, $document]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('841.890 595.280', $response->getContent());
        $this->assertSame(2, preg_match_all('/\/Type\s*\/Page\b/', $response->getContent()));
    }

    public function test_outsider_cannot_download_attendance_pdf(): void
    {
        [, $project] = $this->workspaceAndProject();
        $document = $this->attendanceDocument($project);

        $this->actingAs(User::factory()->create())
            ->get(route('project-documents.attendance', [$project, $document]))
            ->assertForbidden();
    }

    public function test_signed_copy_is_private_and_requires_membership(): void
    {
        Storage::fake('local');
        [$workspace, $project] = $this->workspaceAndProject();
        $member = User::factory()->create();
        $outsider = User::factory()->create();
        $workspace->users()->attach($member, ['role' => 'viewer']);
        $document = $this->attendanceDocument($project);
        $path = 'project-documents/'.$project->id.'/'.$document->id.'/signed.pdf';
        Storage::disk('local')->put($path, 'signed');
        $document->update([
            'signed_path' => $path,
            'signed_disk' => 'local',
            'signed_name' => 'signed-attendance.pdf',
            'signed_size' => 6,
            'signed_at' => now(),
        ]);

        $this->actingAs($outsider)
            ->get(route('project-documents.signed', [$project, $document]))
            ->assertForbidden();

        $this->actingAs($member)
            ->get(route('project-documents.signed', [$project, $document]))
            ->assertOk()
            ->assertDownload('signed-attendance.pdf');
    }

    public function test_deleting_document_removes_signed_file(): void
    {
        Storage::fake('local');
        [, $project] = $this->workspaceAndProject();
        $document = $this->attendanceDocument($project);
        $path = 'project-documents/'.$project->id.'/'.$document->id.'/signed.pdf';
        Storage::disk('local')->put($path, 'signed');
        $document->update(['signed_path' => $path, 'signed_disk' => 'local']);

        $document->delete();

        Storage::disk('local')->assertMissing($path);
    }

    public function test_uploaded_project_file_is_private_and_removed_with_its_record(): void
    {
        Storage::fake('local');
        [$workspace, $project] = $this->workspaceAndProject();
        $member = User::factory()->create();
        $outsider = User::factory()->create();
        $workspace->users()->attach($member, ['role' => 'viewer']);
        $path = 'project-documents/'.$project->id.'/grant.pdf';
        Storage::disk('local')->put($path, 'grant');
        $document = ProjectDocument::create([
            'project_id' => $project->id,
            'type' => ProjectDocument::TYPE_UPLOAD,
            'category' => 'grant_agreement',
            'title' => 'Grant agreement',
            'file_path' => $path,
            'file_disk' => 'local',
            'file_name' => 'Grant Agreement.pdf',
            'file_size' => 5,
        ]);

        $this->actingAs($outsider)
            ->get(route('project-documents.file', [$project, $document]))
            ->assertForbidden();

        $this->actingAs($member)
            ->get(route('project-documents.file', [$project, $document]))
            ->assertOk()
            ->assertDownload('Grant Agreement.pdf');

        $document->delete();
        Storage::disk('local')->assertMissing($path);
    }

    public function test_expense_report_snapshot_filters_rows_and_calculates_totals(): void
    {
        Storage::fake('local');
        [, $project] = $this->workspaceAndProject();
        $travel = $project->budgetLines()->where('title', 'Travel')->firstOrFail();
        $support = $project->budgetLines()->where('title', 'Organisational Support')->firstOrFail();

        $travel->expenses()->create([
            'reference_nr' => 'INV-10',
            'description' => 'Train tickets',
            'expense_date' => '2026-06-10',
            'amount' => 500,
            'currency' => 'RON',
            'exchange_rate' => 5,
            'amount_eur' => 100,
            'attachment_path' => 'expenses/train.pdf',
            'attachment_disk' => 'local',
            'attachment_name' => 'train.pdf',
        ]);
        Storage::disk('local')->put('expenses/train.pdf', 'proof');
        $support->expenses()->create([
            'description' => 'Facilitation',
            'expense_date' => '2026-06-15',
            'amount' => 250,
            'currency' => 'EUR',
            'exchange_rate' => 1,
            'amount_eur' => 250,
        ]);
        $travel->expenses()->create([
            'description' => 'Outside period',
            'expense_date' => '2026-07-01',
            'amount' => 50,
            'currency' => 'EUR',
            'exchange_rate' => 1,
            'amount_eur' => 50,
        ]);

        $snapshot = app(ExpenseReportSnapshot::class)->build(
            $project,
            Carbon::parse('2026-06-01'),
            Carbon::parse('2026-06-30')
        );

        $this->assertSame(2, $snapshot['expense_count']);
        $this->assertSame(350.0, $snapshot['total_eur']);
        $this->assertSame(['INV-10', 'EXP-002'], array_column($snapshot['expenses'], 'reference'));
        $this->assertSame(['Attached', 'Missing'], array_column($snapshot['expenses'], 'evidence'));
        $this->assertEqualsCanonicalizing([
            ['category' => 'Travel', 'amount_eur' => 100.0],
            ['category' => 'Organisational Support', 'amount_eur' => 250.0],
        ], $snapshot['category_totals']);
    }

    public function test_workspace_member_can_download_landscape_expense_report_pdf(): void
    {
        [$workspace, $project] = $this->workspaceAndProject();
        $user = User::factory()->create();
        $workspace->users()->attach($user, ['role' => 'viewer']);
        $document = ProjectDocument::create([
            'project_id' => $project->id,
            'type' => ProjectDocument::TYPE_EXPENSE_REPORT,
            'category' => 'report',
            'title' => 'Official expense report',
            'document_date' => '2026-06-21',
            'metadata' => [
                'period_start' => '2026-06-01',
                'period_end' => '2026-06-30',
                'expense_count' => 1,
                'total_eur' => 100,
                'category_totals' => [['category' => 'Travel', 'amount_eur' => 100]],
                'prepared_by' => 'Project Manager',
                'expenses' => [[
                    'reference' => 'INV-10',
                    'date' => '2026-06-10',
                    'budget_category' => 'Travel',
                    'description' => 'Train tickets',
                    'amount' => 500,
                    'currency' => 'RON',
                    'exchange_rate' => 5,
                    'amount_eur' => 100,
                    'evidence' => 'Attached',
                    'evidence_name' => 'train.pdf',
                    'notes' => null,
                ]],
            ],
            'generated_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('project-documents.expense-report', [$project, $document]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('841.890 595.280', $response->getContent());
    }

    private function workspaceAndProject(): array
    {
        $workspace = Workspace::create(['name' => 'Documents Workspace']);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Youth Exchange',
            'acronym' => 'YE',
            'status' => 'active',
        ]);

        return [$workspace, $project];
    }

    private function attendanceDocument(Project $project): ProjectDocument
    {
        return ProjectDocument::create([
            'project_id' => $project->id,
            'type' => ProjectDocument::TYPE_ATTENDANCE,
            'title' => 'Attendance list - Workshop',
            'activity_title' => 'Workshop',
            'activity_date' => '2026-06-20',
            'location' => 'Bucharest',
            'generated_at' => now(),
        ]);
    }

    private function participants(Project $project): void
    {
        foreach ([
            ['first_name' => 'Zoe', 'last_name' => 'Adams', 'partner_organisation' => 'Association B'],
            ['first_name' => 'Ana', 'last_name' => 'Pop', 'partner_organisation' => 'Association A'],
        ] as $participant) {
            Participant::create($participant + ['project_id' => $project->id]);
        }
    }
}
