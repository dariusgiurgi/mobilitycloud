<?php

namespace Tests\Feature;

use App\Jobs\GenerateProjectFinalArchive;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectFinalArchive;
use App\Models\User;
use App\Services\ProjectFinalArchiveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class ProjectFinalArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_builds_and_stores_the_selected_archive(): void
    {
        Storage::fake('local');

        [$project, $user] = $this->projectAndUser();
        $documentPath = 'project-documents/'.$project->id.'/evidence.pdf';
        Storage::disk('local')->put($documentPath, str_repeat('streamed evidence ', 100_000));

        $document = ProjectDocument::create([
            'project_id' => $project->id,
            'type' => ProjectDocument::TYPE_UPLOAD,
            'category' => 'other',
            'title' => 'Final evidence',
            'file_path' => $documentPath,
            'file_disk' => 'local',
            'file_name' => 'evidence.pdf',
        ]);

        $selection = $this->selection(['project_data', 'project_files']);
        $archive = $project->finalArchives()->create([
            'requested_by' => $user->id,
            'status' => ProjectFinalArchive::STATUS_QUEUED,
            'selection' => $selection,
            'selection_hash' => hash('sha256', json_encode($selection, JSON_THROW_ON_ERROR)),
            'filename' => 'final-archive-youth-exchange.zip',
        ]);

        (new GenerateProjectFinalArchive($archive->id))->handle(app(ProjectFinalArchiveService::class));

        $archive->refresh();
        $this->assertTrue($archive->isReady());
        $this->assertNotNull($archive->sha256);
        $this->assertGreaterThan(0, $archive->size);
        $this->assertTrue($archive->expires_at->isFuture());
        Storage::disk('local')->assertExists($archive->path);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open(Storage::disk('local')->path($archive->path)) === true);
        $this->assertNotFalse($zip->locateName('youth-exchange/09-project-documents/other/'.$document->id.'-final-evidence/original-evidence.pdf'));
        $this->assertNotFalse($zip->locateName('youth-exchange/00-project-data/activity-log.csv'));
        $this->assertFalse($zip->locateName('youth-exchange/04-budget-expenses'));
        $payload = json_decode($zip->getFromName('youth-exchange/00-project-data/project-data.json'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(2, $payload['format_version']);
        $this->assertTrue($payload['included_sections']['project_files']);
        $this->assertFalse($payload['included_sections']['budget']);
        $this->assertSame('csv', $payload['activity_log_export']['format']);
        $zip->close();
    }

    public function test_ready_archive_download_is_streamed_and_audited(): void
    {
        Storage::fake('local');

        [$project, $user] = $this->projectAndUser();
        $path = 'final-archives/'.$project->id.'/archive.zip';
        Storage::disk('local')->put($path, 'zip-content');

        $archive = $project->finalArchives()->create([
            'requested_by' => $user->id,
            'status' => ProjectFinalArchive::STATUS_READY,
            'selection' => $this->selection(['project_data']),
            'selection_hash' => hash('sha256', 'selection'),
            'filename' => 'final-archive-youth-exchange.zip',
            'disk' => 'local',
            'path' => $path,
            'size' => 11,
            'sha256' => hash('sha256', 'zip-content'),
            'completed_at' => now(),
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($user)
            ->get(route('projects.final-archive', [$project, $archive]))
            ->assertOk()
            ->assertDownload('final-archive-youth-exchange.zip')
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private');

        $archive->refresh();
        $this->assertSame(1, $archive->download_count);
        $this->assertNotNull($archive->downloaded_at);
        $this->assertDatabaseHas('project_activity_logs', [
            'project_id' => $project->id,
            'user_id' => $user->id,
            'event' => 'final_archive_downloaded',
            'subject_id' => $archive->id,
        ]);
    }

    public function test_archive_cannot_be_downloaded_through_another_project(): void
    {
        Storage::fake('local');

        [$project, $user] = $this->projectAndUser();
        $otherProject = Project::create([
            'owner_id' => $user->id,
            'access_mode' => 'restricted',
            'name' => 'Other project',
            'status' => 'active',
        ]);
        $archive = $project->finalArchives()->create([
            'requested_by' => $user->id,
            'status' => ProjectFinalArchive::STATUS_READY,
            'selection' => $this->selection(['project_data']),
            'selection_hash' => hash('sha256', 'selection'),
            'filename' => 'archive.zip',
            'disk' => 'local',
            'path' => 'final-archives/archive.zip',
            'size' => 1,
            'sha256' => hash('sha256', 'x'),
            'expires_at' => now()->addDay(),
        ]);

        Storage::disk('local')->put($archive->path, 'x');

        $this->actingAs($user)
            ->get(route('projects.final-archive', [$otherProject, $archive]))
            ->assertNotFound();
    }

    public function test_expired_archives_are_removed_by_the_cleanup_command(): void
    {
        Storage::fake('local');

        [$project, $user] = $this->projectAndUser();
        $path = 'final-archives/'.$project->id.'/expired.zip';
        Storage::disk('local')->put($path, 'x');
        $archive = $project->finalArchives()->create([
            'requested_by' => $user->id,
            'status' => ProjectFinalArchive::STATUS_READY,
            'selection' => $this->selection(['project_data']),
            'selection_hash' => hash('sha256', 'selection'),
            'filename' => 'archive.zip',
            'disk' => 'local',
            'path' => $path,
            'size' => 1,
            'sha256' => hash('sha256', 'x'),
            'expires_at' => now()->subMinute(),
        ]);

        $this->artisan('mobilitycloud:purge-final-archives')->assertSuccessful();

        $archive->refresh();
        $this->assertSame(ProjectFinalArchive::STATUS_EXPIRED, $archive->status);
        $this->assertNull($archive->path);
        Storage::disk('local')->assertMissing($path);
    }

    private function projectAndUser(): array
    {
        $user = User::factory()->create();
        $project = Project::create([
            'owner_id' => $user->id,
            'access_mode' => 'restricted',
            'name' => 'Youth Exchange',
            'status' => 'active',
        ]);

        return [$project, $user];
    }

    private function selection(array $included): array
    {
        return collect(ProjectFinalArchive::CATEGORY_KEYS)
            ->mapWithKeys(fn (string $key): array => [$key => in_array($key, $included, true)])
            ->all();
    }
}
