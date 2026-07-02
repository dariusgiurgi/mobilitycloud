<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\Pages\ViewProjectMobility;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\User;
use App\Models\Workspace;
use App\Services\ProjectFinalArchiveService;
use App\Services\WorkspaceBackupService;
use Filament\Facades\Filament;
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
        [$workspace, $project, $user] = $this->workspaceProjectAndUser();
        $this->actingAs($user);
        Filament::setTenant($workspace);

        Livewire::test(ViewProjectMobility::class, ['record' => $project->id])
            ->assertSee('Mobility workspace')
            ->assertSee('Download final archive')
            ->set('mobilityReport', 'The mobility delivered workshops, worksheets and participant outputs.')
            ->call('saveMobilityReport')
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

        $document = ProjectDocument::query()
            ->where('project_id', $project->id)
            ->where('category', 'mobility_material')
            ->sole();

        $this->assertSame('mobility', data_get($document->metadata, 'source'));
        $this->assertSame('worksheet.pdf', $document->file_name);
        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_final_project_archive_contains_mobility_documents_in_ordered_folder(): void
    {
        Storage::fake('local');
        [, $project] = $this->workspaceProjectAndUser();

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

    public function test_workspace_backup_also_groups_mobility_documents(): void
    {
        Storage::fake('local');
        [$workspace, $project] = $this->workspaceProjectAndUser();

        Storage::disk('local')->put('project-documents/'.$project->id.'/mobility/mobility_plan/plan.pdf', 'plan-file');
        $document = ProjectDocument::create([
            'project_id' => $project->id,
            'type' => ProjectDocument::TYPE_UPLOAD,
            'category' => 'mobility_plan',
            'title' => 'Mobility plan',
            'file_path' => 'project-documents/'.$project->id.'/mobility/mobility_plan/plan.pdf',
            'file_disk' => 'local',
            'file_name' => 'plan.pdf',
            'file_size' => 9,
            'metadata' => ['source' => 'mobility'],
        ]);

        $archive = app(WorkspaceBackupService::class)->create($workspace);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($archive) === true);

        $this->assertNotFalse($zip->locateName('files/'.$project->id.'-youth-exchange/documents/02-mobility/mobility-plan/'.$document->id.'-mobility-plan/original-plan.pdf'));

        $zip->close();
        unlink($archive);
    }

    public function test_mobility_page_is_available_in_project_navigation(): void
    {
        [$workspace, $project, $user] = $this->workspaceProjectAndUser();
        $this->actingAs($user);
        Filament::setTenant($workspace);

        $this->assertArrayHasKey('mobility', ProjectResource::getPages());
        $this->assertStringContainsString('/mobility', ProjectResource::getUrl('mobility', ['record' => $project]));
    }

    private function workspaceProjectAndUser(): array
    {
        $workspace = Workspace::create(['name' => 'Mobility Workspace']);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Youth Exchange',
            'status' => 'active',
        ]);
        $user = User::factory()->create();
        $workspace->users()->attach($user, ['role' => 'member']);

        return [$workspace, $project, $user];
    }
}
