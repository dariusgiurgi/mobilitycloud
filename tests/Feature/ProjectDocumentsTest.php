<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\Pages\ViewProjectDocuments;
use App\Models\Participant;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\User;
use App\Services\ExpenseReportSnapshot;
use App\Services\ProjectDocumentChecklist;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectDocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_owner_can_download_landscape_attendance_pdf(): void
    {
        [$project, $user] = $this->projectAndUser();
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
        [$project] = $this->projectAndUser();
        $document = $this->attendanceDocument($project);

        $this->actingAs(User::factory()->create())
            ->get(route('project-documents.attendance', [$project, $document]))
            ->assertForbidden();
    }

    public function test_signed_copy_is_private_and_requires_membership(): void
    {
        Storage::fake('local');
        [$project, $member] = $this->projectAndUser(Project::PROJECT_ROLE_VIEWER);
        $outsider = User::factory()->create();
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
        [$project] = $this->projectAndUser();
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
        [$project, $member] = $this->projectAndUser(Project::PROJECT_ROLE_VIEWER);
        $outsider = User::factory()->create();
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
        [$project] = $this->projectAndUser();
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

        $evidenceFirst = app(ExpenseReportSnapshot::class)->build(
            $project,
            Carbon::parse('2026-06-01'),
            Carbon::parse('2026-06-30'),
            'evidence'
        );
        $this->assertSame('evidence', $evidenceFirst['order_by']);
        $this->assertSame('Supporting evidence status', $evidenceFirst['order_label']);
        $this->assertSame(['EXP-002', 'INV-10'], array_column($evidenceFirst['expenses'], 'reference'));
        $this->assertSame([1, 2], array_column($evidenceFirst['expenses'], 'row_number'));
    }

    public function test_project_viewer_can_download_landscape_expense_report_pdf(): void
    {
        [$project, $user] = $this->projectAndUser(Project::PROJECT_ROLE_VIEWER);
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

    public function test_document_checklist_reports_file_and_signature_readiness(): void
    {
        Storage::fake('local');
        [$project] = $this->projectAndUser();
        $project->update(['partner_orgs' => [
            ['name' => 'Coordinator Association', 'country' => 'RO', 'oid' => null, 'is_coordinator' => true],
            ['name' => 'Partner Association', 'country' => 'IT', 'oid' => null],
        ]]);

        $grantPath = 'project-documents/'.$project->id.'/grant.pdf';
        Storage::disk('local')->put($grantPath, 'grant');
        ProjectDocument::create([
            'project_id' => $project->id,
            'type' => ProjectDocument::TYPE_UPLOAD,
            'category' => 'grant_agreement',
            'title' => 'Grant agreement',
            'file_path' => $grantPath,
            'file_disk' => 'local',
            'file_name' => 'grant.pdf',
        ]);

        $attendance = $this->attendanceDocument($project);
        $attendancePath = 'project-documents/'.$project->id.'/attendance-signed.pdf';
        Storage::disk('local')->put($attendancePath, 'signed');
        $attendance->update(['signed_path' => $attendancePath, 'signed_disk' => 'local']);

        $agreementPath = 'project-documents/'.$project->id.'/agreement-signed.pdf';
        $paymentPath = 'project-documents/'.$project->id.'/payment-signed.pdf';
        Storage::disk('local')->put($agreementPath, 'signed');
        Storage::disk('local')->put($paymentPath, 'signed');
        $project->budgetLines()->first()->expenses()->create([
            'description' => 'Facilitation',
            'amount' => 1000,
            'currency' => 'EUR',
            'amount_eur' => 1000,
            'is_civil_convention' => true,
            'convention_data' => [
                'convention_number' => 'CC-001',
                'contract_date' => '2026-06-20',
                'provider_name' => 'Alex Example',
                'provider_address' => 'Bucharest',
                'provider_id_number' => 'AB123456',
                'service_description' => 'Facilitation',
                'service_start_date' => '2026-06-20',
                'service_end_date' => '2026-06-21',
                'gross_amount' => 1000,
                'currency' => 'EUR',
                'payment_date' => '2026-06-22',
                'payment_method' => 'bank_transfer',
                'payment_status' => 'paid',
                'agreement_signed_path' => $agreementPath,
                'agreement_signed_disk' => 'local',
                'payment_signed_path' => $paymentPath,
                'payment_signed_disk' => 'local',
            ],
        ]);

        $checklist = app(ProjectDocumentChecklist::class)->build($project->fresh());
        $items = collect($checklist['items'])->keyBy('label');

        $this->assertSame('complete', $items['Grant agreement']['status']);
        $this->assertSame('complete', $items['Attendance records']['status']);
        $this->assertSame('complete', $items['Civil conventions']['status']);
        $this->assertSame('1/1 ready, 1 signed agreement(s); 1 payment evidence record(s), 1 signed', $items['Civil conventions']['detail']);
        $this->assertSame('missing', $items['Partner documents']['status']);
        $this->assertSame('0 partner files for 1 external partner', $items['Partner documents']['detail']);
        $this->assertSame('missing', $items['Expense reports']['status']);
        $this->assertSame(3, $checklist['complete']);
    }

    public function test_documents_page_switches_between_professional_workspace_tabs(): void
    {
        [$project, $user] = $this->projectAndUser();

        $this->actingAs($user);
        Livewire::test(ViewProjectDocuments::class, ['record' => $project->id])
            ->assertSet('activeDocumentTab', 'files')
            ->assertSee('Project document centre')
            ->assertSee('Project readiness')
            ->assertSee('Document readiness')
            ->assertSee('Next:')
            ->assertSee('Add document')
            ->call('setDocumentTab', 'conventions')
            ->assertSet('activeDocumentTab', 'conventions')
            ->assertSee('Civil conventions')
            ->assertSee('Civil convention workflow')
            ->call('setDocumentTab', 'dissemination')
            ->assertSet('activeDocumentTab', 'dissemination')
            ->assertSee('Dissemination evidence')
            ->call('setDocumentTab', 'checklist')
            ->assertSet('activeDocumentTab', 'checklist')
            ->assertSee('Project file checklist')
            ->assertSee('Automatic checklist');
    }

    public function test_checklist_actions_open_relevant_document_workflows(): void
    {
        [$project, $user] = $this->projectAndUser();

        $this->actingAs($user);

        Livewire::test(ViewProjectDocuments::class, ['record' => $project->id])
            ->call('setDocumentTab', 'checklist')
            ->assertSee('Upload')
            ->assertSee('Generate attendance')
            ->assertSee('Generate report')
            ->call('openDocumentUploadFor', 'grant_agreement')
            ->assertSet('showDocumentUploadModal', true)
            ->assertSet('documentCategory', 'grant_agreement')
            ->assertSet('documentTitle', 'Grant agreement')
            ->call('closeDocumentUpload')
            ->call('openAttendanceGenerator')
            ->assertSet('showAttendanceModal', true)
            ->call('closeAttendanceGenerator')
            ->call('openExpenseReportGenerator')
            ->assertSet('showExpenseReportModal', true);
    }

    public function test_member_can_save_dissemination_report_and_upload_evidence_per_organisation(): void
    {
        Storage::fake('local');
        [$project, $user] = $this->projectAndUser();
        $project->update(['partner_orgs' => [
            ['name' => 'Coordinator Association', 'country' => 'RO', 'oid' => 'E10000001', 'is_coordinator' => true],
            ['name' => 'Partner Association', 'country' => 'IT', 'oid' => 'E10000002'],
        ]]);

        $this->actingAs($user);

        $component = Livewire::test(ViewProjectDocuments::class, ['record' => $project->id])
            ->call('setDocumentTab', 'dissemination')
            ->assertSee('Coordinator Association')
            ->assertSee('Partner Association')
            ->assertSee('Save report')
            ->assertSee('Upload evidence');

        $organisation = collect($component->instance()->getDisseminationOrganisations())
            ->firstWhere('name', 'Partner Association');

        $component
            ->set('disseminationReports.'.$organisation['key'], 'Partner organised two local presentations and published campaign screenshots.')
            ->call('saveDisseminationReport', $organisation['key'])
            ->call('openDisseminationUpload', $organisation['key'])
            ->assertSet('showDisseminationUploadModal', true)
            ->set('disseminationUpload', UploadedFile::fake()->create('partner-dissemination.pdf', 120, 'application/pdf'))
            ->call('uploadDisseminationEvidence')
            ->assertSet('showDisseminationUploadModal', false);

        $project->refresh();
        $this->assertSame(
            'Partner organised two local presentations and published campaign screenshots.',
            data_get($project->action_data, 'dissemination_reports.'.$organisation['key'])
        );

        $document = ProjectDocument::query()
            ->where('project_id', $project->id)
            ->where('category', 'dissemination_evidence')
            ->sole();

        $this->assertSame('Partner Association', data_get($document->metadata, 'organisation_name'));
        $this->assertSame($organisation['key'], data_get($document->metadata, 'organisation_key'));
        $this->assertSame('partner-dissemination.pdf', $document->file_name);
        Storage::disk('local')->assertExists($document->file_path);

        $summary = $component->instance()->getDisseminationSummary();
        $this->assertSame(2, $summary['organisations']);
        $this->assertSame(1, $summary['with_reports']);
        $this->assertSame(1, $summary['with_evidence']);
    }

    public function test_civil_conventions_show_step_workflow_and_direct_actions(): void
    {
        [$project, $user] = $this->projectAndUser();
        $expense = $project->budgetLines()->first()->expenses()->create([
            'description' => 'Facilitation services',
            'amount' => 1200,
            'currency' => 'EUR',
            'amount_eur' => 1200,
            'is_civil_convention' => true,
            'convention_data' => [
                'convention_number' => 'CC-001',
                'contract_date' => '2026-06-20',
                'provider_name' => 'Alex Example',
                'provider_address' => 'Bucharest',
                'provider_id_number' => 'AB123456',
                'service_description' => 'Facilitation',
                'service_start_date' => '2026-06-20',
                'service_end_date' => '2026-06-21',
                'gross_amount' => 1200,
                'currency' => 'EUR',
                'payment_date' => '2026-06-22',
                'payment_method' => 'bank_transfer',
                'payment_status' => 'paid',
            ],
        ]);

        $this->actingAs($user);

        $component = Livewire::test(ViewProjectDocuments::class, ['record' => $project->id])
            ->call('setDocumentTab', 'conventions')
            ->assertSee('Complete')
            ->assertSee('Details missing')
            ->assertSee('Awaiting signatures')
            ->assertSee('Details')
            ->assertSee('Agreement PDF')
            ->assertSee('Signed agreement')
            ->assertSee('Payment evidence')
            ->assertSee('Download agreement')
            ->assertSee('Upload signed agreement')
            ->assertSee('Download payment evidence')
            ->assertSee('Upload signed payment evidence');

        $summary = $component->instance()->getCivilConventionSummary();
        $this->assertSame(1, $summary['total']);
        $this->assertSame(0, $summary['complete']);
        $this->assertSame(0, $summary['details_missing']);
        $this->assertSame(1, $summary['awaiting_signatures']);
        $this->assertSame(1, $summary['payment_evidence_ready']);
        $this->assertTrue($expense->fresh()->hasCompleteConventionData());
    }

    public function test_civil_convention_is_complete_with_signed_agreement_only(): void
    {
        Storage::fake('local');
        [$project, $user] = $this->projectAndUser();
        $agreementPath = 'project-documents/'.$project->id.'/agreement-signed.pdf';
        Storage::disk('local')->put($agreementPath, 'signed');
        $project->budgetLines()->first()->expenses()->create([
            'description' => 'Facilitation services',
            'amount' => 1200,
            'currency' => 'EUR',
            'amount_eur' => 1200,
            'is_civil_convention' => true,
            'convention_data' => [
                'convention_number' => 'CC-001',
                'contract_date' => '2026-06-20',
                'provider_name' => 'Alex Example',
                'provider_address' => 'Bucharest',
                'provider_id_number' => 'AB123456',
                'service_description' => 'Facilitation',
                'service_start_date' => '2026-06-20',
                'service_end_date' => '2026-06-21',
                'gross_amount' => 1200,
                'currency' => 'EUR',
                'agreement_signed_path' => $agreementPath,
                'agreement_signed_disk' => 'local',
            ],
        ]);

        $this->actingAs($user);

        $component = Livewire::test(ViewProjectDocuments::class, ['record' => $project->id])
            ->call('setDocumentTab', 'conventions')
            ->assertSee('COMPLETE')
            ->assertSee('Optional after payment');

        $summary = $component->instance()->getCivilConventionSummary();
        $this->assertSame(1, $summary['complete']);
        $this->assertSame(0, $summary['awaiting_signatures']);
        $this->assertSame(0, $summary['payment_evidence_ready']);
    }

    public function test_document_command_center_summarises_readiness_and_awaiting_signatures(): void
    {
        [$project, $user] = $this->projectAndUser();
        $signed = $this->attendanceDocument($project);
        $unsigned = ProjectDocument::create([
            'project_id' => $project->id,
            'type' => ProjectDocument::TYPE_EXPENSE_REPORT,
            'category' => 'report',
            'title' => 'Official expense report',
            'metadata' => ['expense_count' => 0, 'total_eur' => 0],
            'generated_at' => now(),
        ]);

        Storage::fake('local');
        $signedPath = 'project-documents/'.$project->id.'/'.$signed->id.'/signed.pdf';
        Storage::disk('local')->put($signedPath, 'signed');
        $signed->update(['signed_path' => $signedPath, 'signed_disk' => 'local']);

        $this->actingAs($user);

        $component = Livewire::test(ViewProjectDocuments::class, ['record' => $project->id])
            ->assertSee('Document readiness')
            ->assertSee('Awaiting')
            ->assertSee('View pending signatures')
            ->assertSee('Upload signed copy')
            ->set('documentFilter', 'unsigned')
            ->assertSet('documentFilter', 'unsigned');

        $summary = $component->instance()->getDocumentCommandCenter();
        $this->assertSame(2, $summary['generated']);
        $this->assertSame(1, $summary['awaiting_signature']);
        $this->assertSame([$unsigned->id], $component->instance()->getDocuments()->pluck('id')->all());
    }

    public function test_primary_signed_upload_action_hides_after_document_is_signed(): void
    {
        [$project, $user] = $this->projectAndUser();
        $document = $this->attendanceDocument($project);
        Storage::fake('local');
        $signedPath = 'project-documents/'.$project->id.'/'.$document->id.'/signed.pdf';
        Storage::disk('local')->put($signedPath, 'signed');
        $document->update(['signed_path' => $signedPath, 'signed_disk' => 'local']);

        $this->actingAs($user);

        Livewire::test(ViewProjectDocuments::class, ['record' => $project->id])
            ->assertSee('Signed')
            ->assertSee('Replace signed copy')
            ->assertDontSeeHtml('<button wire:click="openSignedUpload('.$document->id.')"');
    }

    private function projectAndUser(string $role = Project::PROJECT_ROLE_EDITOR): array
    {
        $user = User::factory()->create();
        $owner = $role === Project::PROJECT_ROLE_VIEWER
            ? User::factory()->create()
            : $user;

        $project = Project::create([
            'owner_id' => $owner->id,
            'workspace_id' => null,
            'access_mode' => 'restricted',
            'name' => 'Youth Exchange',
            'acronym' => 'YE',
            'status' => 'active',
        ]);

        if ($role === Project::PROJECT_ROLE_VIEWER) {
            $project->members()->attach($user, ['role' => Project::PROJECT_ROLE_VIEWER]);
        }

        return [$project, $user];
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
