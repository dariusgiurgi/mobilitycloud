<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\Pages\ViewProjectFinalisation;
use App\Filament\Resources\Projects\Pages\ViewProjectParticipants;
use App\Jobs\GenerateProjectFinalArchive;
use App\Models\Project;
use App\Models\ProjectActivityLog;
use App\Models\ProjectDocument;
use App\Models\ProjectFinalArchive;
use App\Models\User;
use App\Rules\MaxUploadBatchSize;
use App\Services\ParticipantRegister;
use App\Services\ProjectFinalArchiveService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;
use Tests\TestCase;
use ZipArchive;

class FinalArchiveIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function project(): Project
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $this->actingAs($user);

        return Project::create(['owner_id' => $user->id, 'name' => 'Audit project', 'status' => 'active', 'access_mode' => 'restricted',
            'partner_orgs' => [['name' => 'Partner']], 'action_data' => ['application_flows' => [['title' => 'FLOW_TEXT']], 'private_token' => 'DO_NOT_EXPORT']]);
    }

    public function test_archive_contains_structured_mobilities_generated_pdfs_and_readable_index(): void
    {
        $project = $this->project();
        $mobility = $project->mobilities()->create(['name' => 'Mobility A', 'start_date' => '2026-10-20', 'end_date' => '2026-10-25',
            'workspace_data' => ['report' => 'MOBILITY_REPORT', 'dissemination_reports' => ['partner' => 'DISSEMINATION_TEXT'], 'secret_token' => 'DO_NOT_EXPORT'],
            'participant_registration_token' => 'DO_NOT_EXPORT']);
        $person = $project->participants()->create(['complete_name' => 'Synthetic Participant', 'role' => 'participant']);
        $mobility->participants()->attach($person, ['role' => 'participant', 'status' => 'completed']);
        foreach ([ProjectDocument::TYPE_ATTENDANCE, ProjectDocument::TYPE_EXPENSE_REPORT] as $type) {
            $project->documents()->create(['type' => $type, 'title' => $type, 'metadata' => [], 'generated_at' => now()]);
        }
        $path = app(ProjectFinalArchiveService::class)->create($project);
        try {
            $zip = new ZipArchive;
            $this->assertTrue($zip->open($path) === true);
            $raw = $zip->getFromName('audit-project/00-project-data/project-data.json');
            $payload = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame('Partner', $payload['project']['partner_orgs'][0]['name']);
            $this->assertSame('completed', $payload['participants'][0]['mobilities'][0]['status']);
            $this->assertStringContainsString('FLOW_TEXT', $raw);
            $this->assertStringContainsString('MOBILITY_REPORT', $raw);
            $this->assertStringContainsString('DISSEMINATION_TEXT', $raw);
            $this->assertStringNotContainsString('DO_NOT_EXPORT', $raw);
            foreach ($payload['file_index'] as $entry) {
                $contents = $zip->getFromName($entry['archive_path']);
                $this->assertNotFalse($contents);
                $this->assertSame($entry['sha256'], hash('sha256', $contents));
                if (str_ends_with($entry['archive_path'], '.pdf')) {
                    $this->assertStringStartsWith('%PDF-', $contents);
                }
            }
            $generated = array_filter($payload['file_index'], fn ($entry) => $entry['entity'] === 'project_document' && $entry['slot'] === 'generated');
            $this->assertCount(2, $generated);
            $this->assertStringContainsString('MOBILITY_REPORT', $zip->getFromName('audit-project/INDEX.html'));
            $zip->close();
        } finally {
            unlink($path);
        }
    }

    public function test_referenced_missing_file_is_an_explicit_failure(): void
    {
        $project = $this->project();
        $project->documents()->create(['type' => 'uploaded', 'title' => 'Missing', 'file_disk' => 'local', 'file_path' => 'missing.pdf']);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A referenced archive file is missing');
        app(ProjectFinalArchiveService::class)->create($project);
    }

    public function test_request_after_data_changes_prepares_a_new_archive(): void
    {
        $project = $this->project();
        Queue::fake();
        $selection = array_fill_keys(ProjectFinalArchive::CATEGORY_KEYS, true);
        $archive = $project->finalArchives()->create(['requested_by' => auth()->id(), 'status' => 'queued', 'selection' => $selection,
            'selection_hash' => hash('sha256', json_encode($selection)), 'filename' => 'archive.zip']);
        (new GenerateProjectFinalArchive($archive->id))->handle(app(ProjectFinalArchiveService::class));
        $project->participants()->create(['complete_name' => 'New Participant', 'role' => 'participant']);
        Livewire::test(ViewProjectFinalisation::class, ['record' => $project->id])->call('requestFinalArchive')->assertHasNoErrors();
        $this->assertDatabaseCount('project_final_archives', 2);
        Queue::assertPushed(GenerateProjectFinalArchive::class, 1);
    }

    public function test_logging_failure_does_not_leave_ready_without_a_file_and_retry_recovers(): void
    {
        $project = $this->project();
        $selection = array_fill_keys(ProjectFinalArchive::CATEGORY_KEYS, true);
        $archive = $project->finalArchives()->create(['requested_by' => auth()->id(), 'status' => 'queued', 'selection' => $selection,
            'selection_hash' => hash('sha256', json_encode($selection)), 'filename' => 'archive.zip']);
        $fail = true;
        ProjectActivityLog::creating(function ($log) use (&$fail): void {
            if ($log->event === 'final_archive_ready' && $fail) {
                $fail = false;
                throw new \RuntimeException('Injected logging failure');
            }
        });
        try {
            (new GenerateProjectFinalArchive($archive->id))->handle(app(ProjectFinalArchiveService::class));
            $this->fail('Expected injected error');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Injected logging failure', $exception->getMessage());
        }
        $this->assertSame('processing', $archive->fresh()->status);
        (new GenerateProjectFinalArchive($archive->id))->handle(app(ProjectFinalArchiveService::class));
        $this->assertTrue($archive->fresh()->isReady());
    }

    public function test_failed_archive_cleanup_preserves_the_file_reference_for_retry(): void
    {
        $project = $this->project();
        $archive = $project->finalArchives()->create([
            'requested_by' => auth()->id(), 'status' => 'ready', 'selection' => [],
            'selection_hash' => hash('sha256', '[]'), 'filename' => 'archive.zip',
            'disk' => 'local', 'path' => 'final-archives/retry.zip', 'expires_at' => now()->subMinute(),
        ]);
        $disk = \Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('delete')->once()->with($archive->path)->andReturn(false);
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);

        try {
            $archive->expire();
            $this->fail('Expected failed file cleanup to be reported.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Cleanup will be retried', $exception->getMessage());
        }

        $this->assertSame('final-archives/retry.zip', $archive->fresh()->path);
        $this->assertSame('ready', $archive->fresh()->status);
    }

    public function test_participant_register_pages_results_and_filters_across_pages(): void
    {
        $project = $this->project();
        $rows = [];
        for ($i = 0; $i < 120; $i++) {
            $rows[] = ['project_id' => $project->id, 'complete_name' => sprintf('Person %03d', $i), 'first_name' => 'Person', 'last_name' => (string) $i, 'role' => 'participant'];
        }
        DB::table('participants')->insert($rows);
        $component = Livewire::test(ViewProjectParticipants::class, ['record' => $project->id]);
        $this->assertSame(120, $component->instance()->getParticipants()->total());
        $this->assertCount(50, $component->instance()->getParticipants());
        $component->call('setPage', 3)->assertSee('Person 119')->set('filterSearch', 'Person 119')->assertSee('Person 119');
        $this->assertSame(1, $component->instance()->getParticipants()->total());
    }

    public function test_accelerated_download_still_enforces_project_access(): void
    {
        $project = $this->project();
        config(['mobilitycloud.final_archives.accelerated_downloads' => true]);
        $archive = $project->finalArchives()->create(['requested_by' => auth()->id(), 'status' => 'queued',
            'selection' => array_fill_keys(ProjectFinalArchive::CATEGORY_KEYS, true), 'selection_hash' => 'test', 'filename' => 'archive.zip']);
        (new GenerateProjectFinalArchive($archive->id))->handle(app(ProjectFinalArchiveService::class));
        $archive->refresh();
        $this->get(route('projects.final-archive', [$project, $archive]))->assertOk()
            ->assertHeader('X-Accel-Redirect', '/_protected_archives/'.$project->id.'/'.$archive->uuid.'.zip')
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private');
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser)->get(route('projects.final-archive', [$project, $archive]))->assertForbidden();
    }

    public function test_batch_upload_limit_is_enforced_server_side(): void
    {
        $files = [UploadedFile::fake()->create('a.pdf', 40000), UploadedFile::fake()->create('b.pdf', 40000)];
        $validator = Validator::make(['files' => $files], ['files' => ['array', new MaxUploadBatchSize]]);
        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('60 MB', $validator->errors()->first('files'));
    }

    public function test_late_failure_callback_cannot_delete_a_committed_archive(): void
    {
        $project = $this->project();
        $archive = $project->finalArchives()->create(['requested_by' => auth()->id(), 'status' => 'queued',
            'selection' => array_fill_keys(ProjectFinalArchive::CATEGORY_KEYS, true), 'selection_hash' => 'test', 'filename' => 'archive.zip']);
        $job = new GenerateProjectFinalArchive($archive->id);
        $job->handle(app(ProjectFinalArchiveService::class));
        $job->failed(new \RuntimeException('Late worker failure'));
        $this->assertTrue($archive->fresh()->isReady());
    }

    public function test_database_minor_filter_matches_birthdays_at_leap_year_boundaries(): void
    {
        $project = $this->project();
        foreach ([['2024-02-29', '2006-03-01'], ['2026-02-28', '2008-02-29'], ['2026-03-01', '2008-02-29']] as [$reference, $birth]) {
            $project->update(['start_date' => $reference]);
            $participant = $project->participants()->create(['complete_name' => 'Leap date', 'birth_date' => $birth, 'role' => 'participant']);
            $minor = $participant->isMinor();
            $register = app(ParticipantRegister::class);
            $this->assertSame($minor, $register->minors($project->participants()->getQuery()->whereKey($participant->id), $project)->exists());
            $this->assertSame($minor, $register->minors($project->participants()->getQuery()->whereKey($participant->id))->exists());
            $mobility = $project->mobilities()->create(['name' => 'Leap trip', 'start_date' => $reference]);
            $mobility->participants()->attach($participant, ['role' => 'participant', 'status' => 'confirmed']);
            $this->assertSame($minor, $register->minors($project->participants()->getQuery()->whereKey($participant->id), $project)->exists());
        }
    }
}
