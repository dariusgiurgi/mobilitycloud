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
            ->assertSee('Photo folder and evidence')
            ->assertSee('Dissemination reports by organisation')
            ->assertSee('Upload materials and outputs')
            ->assertDontSee('Download final archive')
            ->set('mobilityReport', 'The mobility delivered workshops, worksheets and participant outputs.')
            ->call('saveMobilityReport')
            ->set('photoFolderUrl', 'https://drive.google.com/drive/folders/mobility-evidence')
            ->call('savePhotoFolderUrl')
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

        $document = ProjectDocument::query()
            ->where('project_id', $project->id)
            ->where('category', 'mobility_material')
            ->sole();

        $this->assertSame('mobility', data_get($document->metadata, 'source'));
        $this->assertSame('worksheet.pdf', $document->file_name);
        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_member_can_upload_multiple_photo_evidence_files(): void
    {
        Storage::fake('local');
        [$project, $user] = $this->projectAndUser();
        $this->actingAs($user);

        Livewire::test(ViewProjectMobility::class, ['record' => $project->id])
            ->set('photoEvidenceTitle', 'Workshop photo evidence')
            ->set('photoEvidenceDate', '2026-07-03')
            ->set('photoEvidenceNotes', 'Group work and presentation proof.')
            ->set('photoUploads', [
                UploadedFile::fake()->image('group-work.jpg'),
                UploadedFile::fake()->image('presentation.png'),
            ])
            ->call('uploadMobilityPhotos')
            ->assertHasNoErrors();

        $documents = ProjectDocument::query()
            ->where('project_id', $project->id)
            ->where('category', 'mobility_photo_video')
            ->orderBy('file_name')
            ->get();

        $this->assertCount(2, $documents);
        $this->assertEqualsCanonicalizing(['group-work.jpg', 'presentation.png'], $documents->pluck('file_name')->all());
        $this->assertTrue($documents->every(fn (ProjectDocument $document): bool => data_get($document->metadata, 'uploaded_from') === 'mobility_photo_batch'));

        $documents->each(fn (ProjectDocument $document) => Storage::disk('local')->assertExists($document->file_path));
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
