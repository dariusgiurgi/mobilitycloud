<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\Pages\ViewProjectMobility;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectMobility;
use App\Models\ProjectModuleLock;
use App\Models\User;
use App\Services\ProjectFinalArchiveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;
use ZipArchive;

class ProjectMobilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobility_workspace_data_and_files_are_scoped_to_the_selected_mobility(): void
    {
        [$project, $user] = $this->projectAndUser(withMobility: false);
        $first = $project->mobilities()->create(['name' => 'Porto', 'start_date' => '2026-07-01', 'end_date' => '2026-07-05']);
        $second = $project->mobilities()->create(['name' => 'Braga', 'start_date' => '2026-08-01', 'end_date' => '2026-08-05', 'sort_order' => 1]);
        $first->documents()->create([
            'project_id' => $project->id,
            'type' => ProjectDocument::TYPE_UPLOAD,
            'category' => 'mobility_material',
            'title' => 'Porto worksheet',
        ]);
        $this->actingAs($user);

        $component = Livewire::test(ViewProjectMobility::class, ['record' => $project->id])
            ->assertSee('Porto')
            ->assertSee('Braga')
            ->assertDontSee('Participants for this mobility')
            ->set('mobilityReport', 'Porto implementation report')
            ->call('saveMobilityReport')
            ->call('selectMobility', $second->id)
            ->assertSet('selectedMobilityId', $second->id)
            ->assertSet('mobilityReport', '')
            ->assertDontSee('Porto worksheet')
            ->set('mobilityReport', 'Braga implementation report')
            ->call('saveMobilityReport');

        $this->assertSame('Porto implementation report', data_get($first->fresh()->workspace_data, 'report'));
        $this->assertSame('Braga implementation report', data_get($second->fresh()->workspace_data, 'report'));
    }

    public function test_mobilities_are_managed_only_from_the_mobility_module_and_limited_to_ten(): void
    {
        [$project, $user] = $this->projectAndUser(withMobility: false);
        $this->actingAs($user);

        $mobilities = collect(range(1, 10))->map(fn (int $number): array => [
            'name' => 'Mobility '.$number,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-05',
        ])->all();

        Livewire::test(ViewProjectMobility::class, ['record' => $project->id])
            ->assertActionVisible('manageMobilities')
            ->callAction('manageMobilities', data: ['mobilities' => $mobilities])
            ->assertHasNoActionErrors()
            ->callAction('manageMobilities', data: ['mobilities' => [...$mobilities, [
                'name' => 'Mobility 11',
                'start_date' => '2026-07-10',
                'end_date' => '2026-07-14',
            ]]])
            ->assertHasActionErrors(['mobilities' => 'max']);

        $this->assertCount(10, ProjectMobility::query()->where('project_id', $project->id)->get());
    }

    public function test_mobility_uses_only_its_selected_organisations_for_dissemination(): void
    {
        [$project, $user] = $this->projectAndUser(withMobility: false);
        $project->update(['partner_orgs' => [
            ['name' => 'Coordinator Association', 'country' => 'RO', 'oid' => 'E10000001', 'is_coordinator' => true],
            ['name' => 'Partner Association', 'country' => 'IT', 'oid' => 'E10000002'],
            ['name' => 'Observer Association', 'country' => 'ES', 'oid' => 'E10000003'],
        ]]);
        $this->actingAs($user);

        Livewire::test(ViewProjectMobility::class, ['record' => $project->id])
            ->callAction('manageMobilities', data: ['mobilities' => [[
                'name' => 'Porto',
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-05',
                'participating_organisations' => ['oid_e10000001', 'oid_e10000002'],
            ]]])
            ->assertHasNoActionErrors()
            ->call('setMobilityTab', 'dissemination')
            ->assertSee('Coordinator Association')
            ->assertSee('Partner Association')
            ->assertDontSee('Observer Association');

        $mobility = $project->fresh()->mobilities()->sole();
        $this->assertSame(['oid_e10000001', 'oid_e10000002'], $mobility->participating_organisations);
    }

    public function test_member_can_save_mobility_report_and_upload_activity_files(): void
    {
        Storage::fake('local');
        [$project, $user] = $this->projectAndUser();
        $mobility = $project->mobilities()->sole();
        $this->actingAs($user);

        Livewire::test(ViewProjectMobility::class, ['record' => $project->id])
            ->assertSee('Choose a mobility')
            ->assertSee('Evidences')
            ->assertSee('Materials')
            ->assertSee('Outputs')
            ->assertSee('Dissemination')
            ->assertSee('Mobility')
            ->assertDontSee('Download final archive')
            ->set('mobilityReport', 'The mobility delivered workshops, worksheets and participant outputs.')
            ->call('saveMobilityReport')
            ->call('setMobilityTab', 'evidences')
            ->assertSee('Evidence by day')
            ->set('newPhotoFolderLabel', 'Drive')
            ->set('newPhotoFolderUrl', 'https://drive.google.com/drive/folders/mobility-evidence')
            ->call('addPhotoFolderLink')
            ->set('finalMobilityVideoUrl', 'https://www.youtube.com/watch?v=mobility-final')
            ->call('saveFinalMobilityVideo')
            ->call('setMobilityTab', 'materials')
            ->assertSee('Upload material or output')
            ->set('documentTitle', 'Activity worksheet')
            ->set('documentCategory', 'mobility_material')
            ->set('documentDate', '2026-07-02')
            ->set('documentNotes', 'Worksheet used during the teamwork session.')
            ->set('documentUpload', UploadedFile::fake()->create('worksheet.pdf', 80, 'application/pdf'))
            ->call('uploadMobilityDocument')
            ->assertHasNoErrors()
            ->assertSee('Activity worksheet');

        $mobility->refresh();
        $this->assertSame('The mobility delivered workshops, worksheets and participant outputs.', data_get($mobility->workspace_data, 'report'));
        $this->assertSame('https://drive.google.com/drive/folders/mobility-evidence', data_get($mobility->workspace_data, 'photo_folder_url'));
        $this->assertSame('Drive', data_get($mobility->workspace_data, 'photo_folder_links.0.label'));
        $this->assertSame('https://www.youtube.com/watch?v=mobility-final', data_get($mobility->workspace_data, 'final_video_url'));

        $document = ProjectDocument::query()
            ->where('project_id', $project->id)
            ->where('category', 'mobility_material')
            ->sole();

        $this->assertSame('mobility', data_get($document->metadata, 'source'));
        $this->assertSame('mobility_page', data_get($document->metadata, 'uploaded_from'));
        $this->assertSame($mobility->id, $document->project_mobility_id);
        $this->assertSame('worksheet.pdf', $document->file_name);
        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_mobility_access_member_can_save_mobility_report_and_upload_activity_files(): void
    {
        Storage::fake('local');
        [$project, $user] = $this->projectAndUser(Project::PROJECT_ROLE_MOBILITY);
        $mobility = $project->mobilities()->sole();
        $this->actingAs($user);

        Livewire::test(ViewProjectMobility::class, ['record' => $project->id])
            ->assertSee('Choose a mobility')
            ->set('mobilityReport', 'Facilitator report about delivered mobility activities.')
            ->call('saveMobilityReport')
            ->call('setMobilityTab', 'materials')
            ->assertSee('Upload material or output')
            ->set('documentTitle', 'Facilitator worksheet')
            ->set('documentCategory', 'mobility_material')
            ->set('documentUpload', UploadedFile::fake()->create('facilitator-worksheet.pdf', 80, 'application/pdf'))
            ->call('uploadMobilityDocument')
            ->assertHasNoErrors()
            ->assertSee('Facilitator worksheet');

        $this->assertSame(
            'Facilitator report about delivered mobility activities.',
            data_get($mobility->fresh()->workspace_data, 'report')
        );
        $this->assertDatabaseHas('project_documents', [
            'project_id' => $project->id,
            'project_mobility_id' => $mobility->id,
            'category' => 'mobility_material',
            'file_name' => 'facilitator-worksheet.pdf',
        ]);
    }

    public function test_member_can_create_evidence_day_with_links_images_and_files(): void
    {
        Storage::fake('local');
        [$project, $user] = $this->projectAndUser();
        $mobility = $project->mobilities()->sole();
        $this->actingAs($user);

        $component = Livewire::test(ViewProjectMobility::class, ['record' => $project->id])
            ->call('setMobilityTab', 'evidences')
            ->assertSee('Evidence by day')
            ->assertDontSee('Autosaved')
            ->call('addEvidenceDay');

        $dayId = array_key_first($component->instance()->evidenceDays);

        $component
            ->set('evidenceDays.'.$dayId.'.title', 'Day 1 - Arrival and workshops')
            ->set('evidenceDays.'.$dayId.'.date', '2026-07-03')
            ->set('evidenceDays.'.$dayId.'.description', 'Participants arrived, worked in mixed teams and created first outputs.')
            ->set('evidenceDays.'.$dayId.'.observations', 'The programme started 30 minutes later because of airport transfers.')
            ->call('addEvidenceLink', $dayId)
            ->assertSet('openEvidenceDays.'.$dayId, true)
            ->assertSet('evidenceUploadDayId', null)
            ->assertSee('Changes save automatically.')
            ->assertSee('Facebook');

        $linkId = $component->instance()->evidenceDays[$dayId]['links'][0]['id'];

        $component
            ->set('evidenceDays.'.$dayId.'.links.0.label', 'Facebook')
            ->set('evidenceDays.'.$dayId.'.links.0.url', 'https://facebook.com/example-post')
            ->assertSet('openEvidenceDays.'.$dayId, true)
            ->assertSet('evidenceUploadDayId', null)
            ->call('prepareEvidenceImageUpload', $dayId)
            ->assertSet('evidenceUploadDayId', $dayId.'_images')
            ->set('evidenceImageTitle', 'Workshop photo evidence')
            ->set('evidenceUploadNotes', 'Group work and presentation proof.')
            ->set('evidenceImageUploads', [
                UploadedFile::fake()->image('group-work.jpg'),
                UploadedFile::fake()->image('presentation.png'),
            ])
            ->call('uploadEvidenceImages', $dayId)
            ->assertSet('openEvidenceDays.'.$dayId, true)
            ->assertSet('evidenceUploadDayId', null)
            ->call('prepareEvidenceFileUpload', $dayId)
            ->assertSet('evidenceUploadDayId', $dayId.'_files')
            ->set('evidenceFileTitle', 'Participant files')
            ->set('evidenceUploadNotes', 'Outputs and presentation used during the day.')
            ->set('evidenceFileUploads', [
                UploadedFile::fake()->create('worksheet.pdf', 80, 'application/pdf'),
                UploadedFile::fake()->create('presentation.pptx', 90, 'application/vnd.openxmlformats-officedocument.presentationml.presentation'),
            ])
            ->call('uploadEvidenceFiles', $dayId)
            ->assertSet('openEvidenceDays.'.$dayId, true)
            ->assertSet('evidenceUploadDayId', null)
            ->assertHasNoErrors();

        $mobility->refresh();
        $this->assertSame('Day 1 - Arrival and workshops', data_get($mobility->workspace_data, 'evidence_days.0.title'));
        $this->assertSame('Facebook', data_get($mobility->workspace_data, 'evidence_days.0.links.0.label'));
        $this->assertSame($linkId, data_get($mobility->workspace_data, 'evidence_days.0.links.0.id'));

        $documents = ProjectDocument::query()
            ->where('project_id', $project->id)
            ->orderBy('file_name')
            ->get();

        $this->assertCount(4, $documents);
        $this->assertTrue($documents->every(fn (ProjectDocument $document): bool => $document->project_mobility_id === $mobility->id));
        $this->assertEqualsCanonicalizing(['group-work.jpg', 'presentation.png', 'presentation.pptx', 'worksheet.pdf'], $documents->pluck('file_name')->all());
        $this->assertTrue($documents->every(fn (ProjectDocument $document): bool => data_get($document->metadata, 'uploaded_from') === 'mobility_evidence_day'));
        $this->assertTrue($documents->every(fn (ProjectDocument $document): bool => data_get($document->metadata, 'evidence_day_id') === $dayId));

        $documents->each(fn (ProjectDocument $document) => Storage::disk('local')->assertExists($document->file_path));

        $imageDocument = $documents->firstWhere('file_name', 'group-work.jpg');

        $this->get(route('project-documents.file', [$project, $imageDocument, 'preview' => 1]))
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');

        $component->call('deleteMobilityDocument', $imageDocument->id);

        $this->assertDatabaseMissing('project_documents', ['id' => $imageDocument->id]);
        Storage::disk('local')->assertMissing($imageDocument->file_path);
    }

    public function test_removing_an_evidence_link_keeps_the_day_open(): void
    {
        [$project, $user] = $this->projectAndUser();
        $this->actingAs($user);

        $component = Livewire::test(ViewProjectMobility::class, ['record' => $project->id])
            ->call('setMobilityTab', 'evidences')
            ->call('addEvidenceDay');

        $dayId = array_key_first($component->instance()->evidenceDays);

        $component
            ->call('addEvidenceLink', $dayId)
            ->assertSet('openEvidenceDays.'.$dayId, true)
            ->assertSet('evidenceUploadDayId', null);

        $linkId = $component->instance()->evidenceDays[$dayId]['links'][0]['id'];

        $component
            ->call('removeEvidenceLink', $dayId, $linkId)
            ->assertSet('openEvidenceDays.'.$dayId, true)
            ->assertSet('evidenceUploadDayId', null);
    }

    public function test_multiple_evidence_days_can_be_collapsed_independently(): void
    {
        [$project, $user] = $this->projectAndUser();
        $this->actingAs($user);

        $component = Livewire::test(ViewProjectMobility::class, ['record' => $project->id])
            ->call('setMobilityTab', 'evidences')
            ->call('addEvidenceDay');

        $firstDayId = array_key_first($component->instance()->evidenceDays);

        $component->call('addEvidenceDay');
        $dayIds = array_keys($component->instance()->evidenceDays);
        $secondDayId = collect($dayIds)->first(fn (string $dayId): bool => $dayId !== $firstDayId);

        $component
            ->assertSet('openEvidenceDays.'.$firstDayId, true)
            ->assertSet('openEvidenceDays.'.$secondDayId, true)
            ->call('prepareEvidenceImageUpload', $firstDayId)
            ->assertSet('evidenceUploadDayId', $firstDayId.'_images')
            ->call('toggleEvidenceDay', $firstDayId)
            ->assertSet('openEvidenceDays.'.$firstDayId, false)
            ->assertSet('evidenceUploadDayId', null)
            ->call('toggleEvidenceDay', $secondDayId)
            ->assertSet('openEvidenceDays.'.$secondDayId, false)
            ->assertSet('evidenceUploadDayId', null);
    }

    public function test_multiple_evidence_days_can_stay_collapsed_after_autosave(): void
    {
        [$project, $user] = $this->projectAndUser();
        $this->actingAs($user);

        $component = Livewire::test(ViewProjectMobility::class, ['record' => $project->id])
            ->call('setMobilityTab', 'evidences')
            ->call('addEvidenceDay');

        $firstDayId = array_key_first($component->instance()->evidenceDays);

        $component->call('addEvidenceDay');
        $secondDayId = collect(array_keys($component->instance()->evidenceDays))
            ->first(fn (string $dayId): bool => $dayId !== $firstDayId);

        $component
            ->set('evidenceDays.'.$firstDayId.'.description', 'First autosaved text')
            ->set('evidenceDays.'.$secondDayId.'.description', 'Second autosaved text')
            ->call('toggleEvidenceDay', $firstDayId)
            ->call('toggleEvidenceDay', $secondDayId)
            ->assertSet('openEvidenceDays.'.$firstDayId, false)
            ->assertSet('openEvidenceDays.'.$secondDayId, false)
            ->assertSet('evidenceUploadDayId', null)
            ->assertDontSee('Changes save automatically.');
    }

    public function test_legacy_evidence_day_open_marker_does_not_force_card_open(): void
    {
        [$project, $user] = $this->projectAndUser();
        $this->actingAs($user);

        $component = Livewire::test(ViewProjectMobility::class, ['record' => $project->id])
            ->call('setMobilityTab', 'evidences')
            ->call('addEvidenceDay');

        $dayId = array_key_first($component->instance()->evidenceDays);

        $component
            ->set('openEvidenceDays.'.$dayId, false)
            ->set('evidenceUploadDayId', $dayId.'_open')
            ->assertDontSee('Changes save automatically.');
    }

    public function test_toggling_one_evidence_day_does_not_open_other_days(): void
    {
        [$project, $user] = $this->projectAndUser();
        $this->actingAs($user);

        $component = Livewire::test(ViewProjectMobility::class, ['record' => $project->id])
            ->call('setMobilityTab', 'evidences')
            ->call('addEvidenceDay');

        $firstDayId = array_key_first($component->instance()->evidenceDays);

        $component->call('addEvidenceDay');
        $secondDayId = collect(array_keys($component->instance()->evidenceDays))
            ->first(fn (string $dayId): bool => $dayId !== $firstDayId);

        $component
            ->call('toggleEvidenceDay', $firstDayId)
            ->call('toggleEvidenceDay', $secondDayId)
            ->assertSet('openEvidenceDays.'.$firstDayId, false)
            ->assertSet('openEvidenceDays.'.$secondDayId, false)
            ->call('toggleEvidenceDay', $firstDayId)
            ->assertSet('openEvidenceDays.'.$firstDayId, true)
            ->assertSet('openEvidenceDays.'.$secondDayId, false);
    }

    public function test_releasing_an_evidence_day_lock_does_not_change_collapsed_state(): void
    {
        [$project, $user] = $this->projectAndUser();
        $this->actingAs($user);
        $this->seedEvidenceDays($project);

        Livewire::test(ViewProjectMobility::class, ['record' => $project->id])
            ->call('toggleEvidenceDay', 'day_2')
            ->assertSet('openEvidenceDays.day_1', null)
            ->assertSet('openEvidenceDays.day_2', true)
            ->call('startProjectEditing', 'mobility', 'evidence-day:day_1', 'Evidence day: Day 1')
            ->call('stopProjectEditing', 'mobility', 'evidence-day:day_1')
            ->assertSet('openEvidenceDays.day_1', null)
            ->assertSet('openEvidenceDays.day_2', true);

        $this->assertFalse(ProjectModuleLock::query()
            ->where('project_id', $project->id)
            ->where('module', 'mobility')
            ->where('lock_key', 'evidence-day:day_1')
            ->exists());
    }

    public function test_member_can_save_dissemination_report_and_upload_evidence_per_organisation_from_mobility(): void
    {
        Storage::fake('local');
        [$project, $user] = $this->projectAndUser();
        $mobility = $project->mobilities()->sole();
        $project->update(['partner_orgs' => [
            ['name' => 'Coordinator Association', 'country' => 'RO', 'oid' => 'E10000001', 'is_coordinator' => true],
            ['name' => 'Partner Association', 'country' => 'IT', 'oid' => 'E10000002'],
        ]]);

        $this->actingAs($user);

        $component = Livewire::test(ViewProjectMobility::class, ['record' => $project->id])
            ->call('setMobilityTab', 'dissemination')
            ->assertSee('Dissemination reports by organisation')
            ->assertSee('Coordinator Association')
            ->assertSee('Partner Association')
            ->assertSee('Prepare upload');

        $organisation = collect($component->instance()->getDisseminationOrganisations())
            ->firstWhere('name', 'Partner Association');

        $component
            ->set('disseminationReports.'.$organisation['key'], 'Partner organised two local presentations and published campaign screenshots.')
            ->call('saveDisseminationReport', $organisation['key'])
            ->call('prepareDisseminationUpload', $organisation['key'])
            ->assertSet('disseminationUploadOrgKey', $organisation['key'])
            ->set('disseminationUploads', [
                UploadedFile::fake()->image('partner-dissemination.jpg'),
                UploadedFile::fake()->create('partner-dissemination.pdf', 120, 'application/pdf'),
            ])
            ->call('uploadDisseminationEvidence', $organisation['key'])
            ->assertHasNoErrors()
            ->assertSet('disseminationUploadOrgKey', null);

        $mobility->refresh();
        $this->assertSame(
            'Partner organised two local presentations and published campaign screenshots.',
            data_get($mobility->workspace_data, 'dissemination_reports.'.$organisation['key'])
        );

        $documents = ProjectDocument::query()
            ->where('project_id', $project->id)
            ->where('category', 'dissemination_evidence')
            ->orderBy('file_name')
            ->get();

        $this->assertCount(2, $documents);
        $this->assertTrue($documents->every(fn (ProjectDocument $document): bool => $document->project_mobility_id === $mobility->id));
        $this->assertTrue($documents->every(fn (ProjectDocument $document): bool => data_get($document->metadata, 'organisation_name') === 'Partner Association'));
        $this->assertTrue($documents->every(fn (ProjectDocument $document): bool => data_get($document->metadata, 'organisation_key') === $organisation['key']));

        $documents->each(fn (ProjectDocument $document) => Storage::disk('local')->assertExists($document->file_path));

        $imageDocument = $documents->firstWhere('file_name', 'partner-dissemination.jpg');
        $this->assertTrue($imageDocument->isImageFile());

        $this->get(route('project-documents.file', [$project, $imageDocument, 'preview' => 1]))
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');

        $summary = $component->instance()->getDisseminationSummary();
        $this->assertSame(2, $summary['organisations']);
        $this->assertSame(1, $summary['with_reports']);
        $this->assertSame(1, $summary['with_evidence']);
    }

    public function test_final_project_archive_contains_mobility_documents_in_ordered_folder(): void
    {
        Storage::fake('local');
        [$project] = $this->projectAndUser();

        Storage::disk('local')->put('project-documents/'.$project->id.'/mobility/mobility_material/worksheet.pdf', 'worksheet-file');
        $document = ProjectDocument::create([
            'project_id' => $project->id,
            'type' => ProjectDocument::TYPE_UPLOAD,
            'category' => 'mobility_material',
            'title' => 'Team worksheet',
            'document_date' => '2026-07-02',
            'file_path' => 'project-documents/'.$project->id.'/mobility/mobility_material/worksheet.pdf',
            'file_disk' => 'local',
            'file_name' => 'worksheet.pdf',
            'file_size' => 14,
            'metadata' => ['source' => 'mobility'],
        ]);

        $archive = app(ProjectFinalArchiveService::class)->create($project);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($archive) === true);

        $this->assertNotFalse($zip->locateName('youth-exchange/00-project-data/project-data.json'));
        $this->assertNotFalse($zip->locateName('youth-exchange/07-mobility/mobility-material-worksheet/'.$document->id.'-team-worksheet/original-worksheet.pdf'));

        $payload = json_decode($zip->getFromName('youth-exchange/00-project-data/project-data.json'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('Youth Exchange', $payload['project']['name']);
        $this->assertSame('project_document', collect($payload['file_index'])->firstWhere('record_id', $document->id)['entity']);

        $zip->close();
        unlink($archive);
    }

    public function test_mobility_page_is_available_in_project_navigation(): void
    {
        [$project, $user] = $this->projectAndUser();
        $this->actingAs($user);

        $this->assertArrayHasKey('mobility', ProjectResource::getPages());
        $this->assertStringContainsString('/mobility', ProjectResource::getUrl('mobility', ['record' => $project]));
    }

    public function test_evidence_day_text_syncs_to_other_users_on_collaboration_refresh(): void
    {
        [$project, $editorA] = $this->projectAndUser();
        $editorB = User::factory()->create();
        $project->members()->attach($editorB, ['role' => Project::PROJECT_ROLE_EDITOR]);
        $mobility = $project->mobilities()->sole();
        $mobility->update([
            'workspace_data' => [
                'evidence_days' => [[
                    'id' => 'day_1',
                    'title' => 'Day 1',
                    'date' => '2026-07-03',
                    'description' => 'Original description',
                    'observations' => '',
                    'links' => [],
                ]],
            ],
        ]);

        $this->actingAs($editorB);
        $editorBComponent = Livewire::test(ViewProjectMobility::class, ['record' => $project->id])
            ->assertSet('evidenceDays.day_1.description', 'Original description');

        $this->actingAs($editorA);
        Livewire::test(ViewProjectMobility::class, ['record' => $project->id])
            ->set('evidenceDays.day_1.description', 'Updated from editor A');

        $this->actingAs($editorB);
        $editorBComponent
            ->call('refreshProjectCollaboration', 'mobility')
            ->assertSet('evidenceDays.day_1.description', 'Updated from editor A');
    }

    public function test_collaboration_refresh_does_not_overwrite_the_users_own_locked_evidence_day(): void
    {
        [$project, $editorA] = $this->projectAndUser();
        $editorB = User::factory()->create();
        $project->members()->attach($editorB, ['role' => Project::PROJECT_ROLE_EDITOR]);
        $this->seedEvidenceDays($project);

        $this->actingAs($editorA);
        $editorAComponent = Livewire::test(ViewProjectMobility::class, ['record' => $project->id])
            ->call('startProjectEditing', 'mobility', 'evidence-day:day_1', 'Evidence day: Day 1')
            ->set('evidenceDays.day_1.description', 'Local draft from editor A');

        $mobility = $project->mobilities()->sole();
        $data = $mobility->workspace_data;
        data_set($data, 'evidence_days.0.description', 'Remote saved text for day 1');
        data_set($data, 'evidence_days.1.description', 'Remote saved text for day 2');
        $mobility->update(['workspace_data' => $data]);

        $editorAComponent
            ->call('refreshProjectCollaboration', 'mobility')
            ->assertSet('evidenceDays.day_1.description', 'Local draft from editor A')
            ->assertSet('evidenceDays.day_2.description', 'Remote saved text for day 2');
    }

    public function test_stale_evidence_day_save_does_not_overwrite_another_day_saved_by_another_user(): void
    {
        [$project, $editorA] = $this->projectAndUser();
        $editorB = User::factory()->create();
        $project->members()->attach($editorB, ['role' => Project::PROJECT_ROLE_EDITOR]);
        $this->seedEvidenceDays($project);

        $this->actingAs($editorA);
        $editorAComponent = Livewire::test(ViewProjectMobility::class, ['record' => $project->id]);

        $this->actingAs($editorB);
        Livewire::test(ViewProjectMobility::class, ['record' => $project->id])
            ->set('evidenceDays.day_2.description', 'Editor B saved day 2');

        $this->actingAs($editorA);
        $editorAComponent
            ->set('evidenceDays.day_1.description', 'Editor A saved day 1');

        $mobility = $project->mobilities()->sole()->fresh();
        $this->assertSame('Editor A saved day 1', data_get($mobility->workspace_data, 'evidence_days.0.description'));
        $this->assertSame('Editor B saved day 2', data_get($mobility->workspace_data, 'evidence_days.1.description'));
    }

    public function test_stale_mobility_report_save_preserves_fresh_evidence_day_changes(): void
    {
        [$project, $editorA] = $this->projectAndUser();
        $editorB = User::factory()->create();
        $project->members()->attach($editorB, ['role' => Project::PROJECT_ROLE_EDITOR]);
        $this->seedEvidenceDays($project);

        $this->actingAs($editorA);
        $editorAComponent = Livewire::test(ViewProjectMobility::class, ['record' => $project->id]);

        $this->actingAs($editorB);
        Livewire::test(ViewProjectMobility::class, ['record' => $project->id])
            ->set('evidenceDays.day_2.description', 'Fresh evidence text from editor B');

        $this->actingAs($editorA);
        $editorAComponent
            ->set('mobilityReport', 'Editor A report text')
            ->call('saveMobilityReport');

        $mobility = $project->mobilities()->sole()->fresh();
        $this->assertSame('Editor A report text', data_get($mobility->workspace_data, 'report'));
        $this->assertSame('Fresh evidence text from editor B', data_get($mobility->workspace_data, 'evidence_days.1.description'));
    }

    private function projectAndUser(string $role = Project::PROJECT_ROLE_EDITOR, bool $withMobility = true): array
    {
        $project = Project::create([
            'name' => 'Youth Exchange',
            'status' => 'active',
        ]);
        $owner = User::factory()->create();
        $project->update(['owner_id' => $owner->id]);
        $user = User::factory()->create();
        $project->members()->attach($user, ['role' => $role]);

        if ($withMobility) {
            $project->mobilities()->create([
                'name' => 'Mobility 1',
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-05',
            ]);
        }

        return [$project, $user];
    }

    private function seedEvidenceDays(Project $project): void
    {
        $project->mobilities()->sole()->update([
            'workspace_data' => [
                'evidence_days' => [
                    [
                        'id' => 'day_1',
                        'title' => 'Day 1',
                        'date' => '2026-07-03',
                        'description' => 'Original day 1 description',
                        'observations' => '',
                        'links' => [],
                    ],
                    [
                        'id' => 'day_2',
                        'title' => 'Day 2',
                        'date' => '2026-07-04',
                        'description' => 'Original day 2 description',
                        'observations' => '',
                        'links' => [],
                    ],
                ],
            ],
        ]);
    }
}
