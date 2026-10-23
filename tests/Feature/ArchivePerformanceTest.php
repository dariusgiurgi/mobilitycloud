<?php

namespace Tests\Feature;

use App\Jobs\GenerateProjectFinalArchive;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectFinalArchive;
use App\Models\User;
use App\Services\ProjectDocumentRenderer;
use App\Services\ProjectFinalArchiveService;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

class ArchivePerformanceTest extends TestCase
{
    use RefreshDatabase;

    private function archive(): ProjectFinalArchive
    {
        Storage::fake('local');
        $this->travelTo(now()->startOfSecond());
        $user = User::factory()->create(['name' => 'PRIVATE_PERSON', 'email' => 'private@example.test']);
        $this->actingAs($user);
        $project = Project::create(['owner_id' => $user->id, 'name' => 'PRIVATE_PROJECT', 'status' => 'active']);
        $project->applicationSections()->create(['title' => 'PRIVATE_QUESTION', 'content' => 'PRIVATE_ANSWER']);
        $project->participants()->create(['complete_name' => 'PRIVATE_PERSON', 'role' => 'participant', 'medical_conditions' => 'PRIVATE_MEDICAL']);
        Storage::disk('local')->put('private-source.txt', 'PRIVATE_CONTENT');
        $project->documents()->create(['type' => ProjectDocument::TYPE_UPLOAD, 'title' => 'PRIVATE_DOCUMENT',
            'file_disk' => 'local', 'file_path' => 'private-source.txt', 'file_name' => 'PRIVATE_FILENAME.txt']);
        foreach ([ProjectDocument::TYPE_ATTENDANCE, ProjectDocument::TYPE_EXPENSE_REPORT] as $type) {
            $project->documents()->create(['type' => $type, 'title' => $type, 'metadata' => [], 'generated_at' => now()]);
        }
        $archive = $project->finalArchives()->create(['requested_by' => $user->id, 'status' => 'queued',
            'selection' => array_fill_keys(ProjectFinalArchive::CATEGORY_KEYS, true), 'selection_hash' => 'qa', 'filename' => 'archive.zip']);
        $archive->forceFill(['created_at' => now()->subSeconds(125)])->save();

        return $archive;
    }

    private function captureLogs(array &$events): void
    {
        $logger = Mockery::mock();
        $logger->shouldReceive('info')->with('Final archive attempt', Mockery::type('array'))
            ->andReturnUsing(function ($message, $context) use (&$events): void {
                $events[] = $context;
            });
        Log::shouldReceive('channel')->with('performance')->andReturn($logger);
    }

    public function test_success_records_phases_volumes_and_queue_wait_without_private_content(): void
    {
        $archive = $this->archive();
        $events = [];
        $this->captureLogs($events);
        (new GenerateProjectFinalArchive($archive->id))->handle(app(ProjectFinalArchiveService::class));
        $archive->refresh();
        $this->assertTrue($archive->isReady());
        $this->assertCount(1, $events);
        $event = $events[0];
        $this->assertSame('ready', $event['outcome']);
        $this->assertSame(125000, $event['queue_wait_ms']);
        $this->assertSame($archive->size, $event['bytes']);
        foreach (['archive_start', 'snapshot', 'serialize', 'pdf', 'file_copy', 'generated_write', 'activity_csv', 'zip_finalize', 'archive_store', 'archive_verify', 'archive_commit', 'archive_cleanup'] as $phase) {
            $this->assertGreaterThanOrEqual(0, $event['phases'][$phase]['duration_ms'], $phase);
            $this->assertGreaterThan(0, $event['phases'][$phase]['calls'], $phase);
            $this->assertSame(0, $event['phases'][$phase]['failures'], $phase);
        }
        $this->assertSame(4, $event['phases']['pdf']['calls']);
        $this->assertSame(1, $event['counters']['copied_files']);
        $this->assertSame(strlen('PRIVATE_CONTENT'), $event['counters']['copied_bytes']);
        $this->assertStringNotContainsString('PRIVATE_', json_encode($event));
        $this->assertStringNotContainsString('private@example.test', json_encode($event));
        $this->assertStringNotContainsString('private-source', json_encode($event));

        $zip = new ZipArchive;
        $this->assertTrue($zip->open(Storage::disk('local')->path($archive->path)) === true);
        $this->assertSame($zip->numFiles, $event['counters']['zip_entries']);
        $payload = json_decode($zip->getFromName('private-project/00-project-data/project-data.json'), true, flags: JSON_THROW_ON_ERROR);
        $paths = array_column($payload['file_index'], 'archive_path');
        foreach (['01-application/application.pdf', '04-budget-expenses/financial-report.pdf', '03-participants/participants.csv'] as $path) {
            $this->assertContains('private-project/'.$path, $paths);
            $this->assertStringContainsString($path, $zip->getFromName('private-project/INDEX.html'));
        }
        $zip->close();
    }

    public function test_failed_pdf_and_retry_have_separate_metrics_and_keep_the_original_error_private(): void
    {
        $archive = $this->archive();
        $events = [];
        $this->captureLogs($events);
        $this->mock(ProjectDocumentRenderer::class)->shouldReceive('render')->once()->andThrow(new RuntimeException('PRIVATE_RENDER_ERROR'));
        $service = app(ProjectFinalArchiveService::class);
        $job = new GenerateProjectFinalArchive($archive->id);
        try {
            $job->handle($service);
            $this->fail('Expected PDF error');
        } catch (RuntimeException $exception) {
            $this->assertSame('PRIVATE_RENDER_ERROR', $exception->getMessage());
        }
        $this->assertSame('failed', $events[0]['outcome']);
        $this->assertSame(1, $events[0]['phases']['pdf']['failures']);
        $this->assertArrayNotHasKey('zip_finalize', $events[0]['phases']);
        $this->assertStringNotContainsString('PRIVATE_', json_encode($events[0]));
        $this->assertSame('processing', $archive->fresh()->status);

        $this->app->forgetInstance(ProjectDocumentRenderer::class);
        $queueJob = Mockery::mock(Job::class);
        $queueJob->shouldReceive('attempts')->andReturn(2);
        $job->setJob($queueJob);
        $job->handle($service);
        $this->assertTrue($archive->fresh()->isReady());
        $this->assertCount(2, $events);
        $this->assertSame('ready', $events[1]['outcome']);
        $this->assertSame(2, $events[1]['attempt']);
        $this->assertNull($events[1]['queue_wait_ms']);
        $this->assertSame(125000, $events[1]['request_age_ms']);
        $this->assertSame(4, $events[1]['phases']['pdf']['calls']);
        $this->assertSame(0, $events[1]['phases']['pdf']['failures']);
    }

    public function test_performance_log_failure_cannot_break_a_valid_archive(): void
    {
        $archive = $this->archive();
        Log::shouldReceive('channel')->with('performance')->andThrow(new RuntimeException('Log unavailable'));
        (new GenerateProjectFinalArchive($archive->id))->handle(app(ProjectFinalArchiveService::class));
        $this->assertTrue($archive->fresh()->isReady());
    }

    public function test_performance_log_failure_cannot_replace_the_export_error(): void
    {
        $archive = $this->archive();
        Storage::disk('local')->delete('private-source.txt');
        Log::shouldReceive('channel')->with('performance')->andThrow(new RuntimeException('Log unavailable'));
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A referenced archive file is missing:');
        (new GenerateProjectFinalArchive($archive->id))->handle(app(ProjectFinalArchiveService::class));
    }
}
