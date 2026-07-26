<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\Pages\ViewProjectMobility;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use App\Models\ProjectDocument;
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

    public function test_member_can_save_mobility_report_and_upload_activity_files(): void
    {
        Storage::fake('local');
        [$project, $user] = $this->projectAndUser();
        $this->actingAs($user);

        Livewire::test(ViewProjectMobility::class, ['record' => $project->id])
            ->assertSee('Mobility workspace')
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

        $project->refresh();
        $this->assertSame('The mobility delivered workshops, worksheets and participant outputs.', data_get($project->action_data, 'mobility.report'));
        $this->assertSame('https://drive.google.com/drive/folders/mobility-evidence', data_get($project->action_data, 'mobility.photo_folder_url'));
        $this->assertSame('Drive', data_get($project->action_data, 'mobility.photo_folder_links.0.label'));
        $this->assertSame('https://www.youtube.com/watch?v=mobility-final', data_get($project->action_data, 'mobility.final_video_url'));

        $document = ProjectDocument::query()
            ->where('project_id', $project->id)
            ->where('category', 'mobility_material')
            ->sole();

        $this->assertSame('mobility', data_get($document->metadata, 'source'));
        $this->assertSame('mobility_page', data_get($document->metadata, 'uploaded_from'));
        $this->assertSame('worksheet.pdf', $document->file_name);
        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_member_can_create_evidence_day_with_links_images_and_files(): void
    {
        Storage::fake('local');
        [$project, $user] = $this->projectAndUser();
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
            ->assertSet('evidenceUploadDayId', $dayId.'_open')
            ->assertSee('Changes save automatically.')
            ->assertSee('Facebook');

        $linkId = $component->instance()->evidenceDays[$dayId]['links'][0]['id'];

        $component
            ->set('evidenceDays.'.$dayId.'.links.0.label', 'Facebook')
            ->set('evidenceDays.'.$dayId.'.links.0.url', 'https://facebook.com/example-post')
            ->assertSet('evidenceUploadDayId', $dayId.'_open')
            ->call('prepareEvidenceImageUpload', $dayId)
            ->assertSet('evidenceUploadDayId', $dayId.'_images')
            ->set('evidenceImageTitle', 'Workshop photo evidence')
            ->set('evidenceUploadNotes', 'Group work and presentation proof.')
            ->set('evidenceImageUploads', [
                UploadedFile::fake()->image('group-work.jpg'),
                UploadedFile::fake()->image('presentation.png'),
            ])
            ->call('uploadEvidenceImages', $dayId)
            ->assertSet('evidenceUploadDayId', $dayId.'_open')
            ->call('prepareEvidenceFileUpload', $dayId)
            ->assertSet('evidenceUploadDayId', $dayId.'_files')
            ->set('evidenceFileTitle', 'Participant files')
            ->set('evidenceUploadNotes', 'Outputs and presentation used during the day.')
            ->set('evidenceFileUploads', [
                UploadedFile::fake()->create('worksheet.pdf', 80, 'application/pdf'),
                UploadedFile::fake()->create('presentation.pptx', 90, 'application/vnd.openxmlformats-officedocument.presentationml.presentation'),
            ])
            ->call('uploadEvidenceFiles', $dayId)
            ->assertSet('evidenceUploadDayId', $dayId.'_open')
            ->assertHasNoErrors();

        $project->refresh();
        $this->assertSame('Day 1 - Arrival and workshops', data_get($project->action_data, 'mobility.evidence_days.0.title'));
        $this->assertSame('Facebook', data_get($project->action_data, 'mobility.evidence_days.0.links.0.label'));
        $this->assertSame($linkId, data_get($project->action_data, 'mobility.evidence_days.0.links.0.id'));

        $documents = ProjectDocument::query()
            ->where('project_id', $project->id)
            ->orderBy('file_name')
            ->get();

        $this->assertCount(4, $documents);
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
            ->assertSet('evidenceUploadDayId', $dayId.'_open');

        $linkId = $component->instance()->evidenceDays[$dayId]['links'][0]['id'];

        $component
            ->call('removeEvidenceLink', $dayId, $linkId)
            ->assertSet('evidenceUploadDayId', $dayId.'_open');
    }

    public function test_member_can_save_dissemination_report_and_upload_evidence_per_organisation_from_mobility(): void
    {
        Storage::fake('local');
        [$project, $user] = $this->projectAndUser();
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

        $project->refresh();
        $this->assertSame(
            'Partner organised two local presentations and published campaign screenshots.',
            data_get($project->action_data, 'dissemination_reports.'.$organisation['key'])
        );

        $documents = ProjectDocument::query()
            ->where('project_id', $project->id)
            ->where('category', 'dissemination_evidence')
            ->orderBy('file_name')
            ->get();

        $this->assertCount(2, $documents);
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

    private function projectAndUser(): array
    {
        $project = Project::create([
            'name' => 'Youth Exchange',
            'status' => 'active',
        ]);
        $user = User::factory()->create();
        $project->members()->attach($user, ['role' => Project::PROJECT_ROLE_EDITOR]);

        return [$project, $user];
    }
}
