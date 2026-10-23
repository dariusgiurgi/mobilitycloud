<?php

namespace Tests\Feature;

use App\Filament\Resources\PlatformActivities\PlatformActivityResource;
use App\Models\MobilityFeedbackCampaign;
use App\Models\PlatformAuditLog;
use App\Models\Project;
use App\Models\ProjectFinalArchive;
use App\Models\User;
use App\Services\ParticipantCsvImporter;
use App\Services\ProjectFinalArchiveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class CsvExportSafetyTest extends TestCase
{
    use RefreshDatabase;

    private function project(): Project
    {
        $owner = User::factory()->create(['name' => '=1+1', 'email' => '+owner@example.test']);
        $this->actingAs($owner);

        return Project::create(['owner_id' => $owner->id, 'name' => 'CSV safety', 'status' => 'active', 'access_mode' => 'restricted']);
    }

    private function rows(string $csv, string $delimiter = ','): array
    {
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, preg_replace('/^\xEF\xBB\xBF/', '', $csv));
        rewind($stream);
        $rows = [];
        while (($row = fgetcsv($stream, null, $delimiter, '"', '')) !== false) {
            $rows[] = $row;
        }
        fclose($stream);

        return $rows;
    }

    public function test_anonymous_feedback_and_question_labels_are_exported_as_text(): void
    {
        $project = $this->project();
        $mobility = $project->mobilities()->create(['name' => 'QA mobility']);
        $campaign = MobilityFeedbackCampaign::create([
            'project_mobility_id' => $mobility->id, 'created_by' => auth()->id(), 'title' => 'QA feedback',
            'public_token' => str_repeat('a', 64), 'opened_at' => now(),
            'form_snapshot' => ['questions' => [['id' => 'comment', 'type' => 'long_text', 'label' => '=1+1', 'required' => true]]],
        ]);
        $this->app['auth']->forgetGuards();
        $this->assertGuest();
        $this->post(route('public.mobility-feedback.store', $campaign->public_token), ['answers' => ['comment' => '=1+1']])->assertRedirect();
        $this->assertSame('=1+1', $campaign->responses()->sole()->answers['comment']);
        $response = $this->actingAs($project->ownerAccount)->get(route('feedback-campaigns.export', $campaign))->assertOk();
        $rows = $this->rows($response->streamedContent());

        $this->assertSame("'=1+1", $rows[0][1]);
        $this->assertSame("'=1+1", $rows[1][1]);
        $this->assertSame('=1+1', $campaign->responses()->sole()->answers['comment']);
    }

    public function test_participant_csv_handles_whitespace_and_keeps_quoted_multiline_values_intact(): void
    {
        $project = $this->project();
        $literal = "Street \\\";=2+2\nȘcoală, România";
        $participant = $project->participants()->create(['complete_name' => '＋1+1', 'phone' => " \t=1+1", 'address' => $literal, 'role' => 'participant']);
        $rows = $this->rows($this->get(route('projects.export-participants', $project))->assertOk()->streamedContent(), ';');

        $this->assertCount(2, $rows);
        $this->assertCount(20, $rows[1]);
        $this->assertSame("'＋1+1", $rows[1][0]);
        $this->assertSame("' \t=1+1", $rows[1][9]);
        $this->assertSame($literal, $rows[1][10]);
        $this->assertSame(" \t=1+1", $participant->fresh()->phone);
    }

    public function test_project_activity_export_protects_the_user_supplied_actor_name(): void
    {
        $project = $this->project();
        $rows = $this->rows($this->get(route('projects.activity.export', $project))->assertOk()->streamedContent());

        $this->assertSame("'=1+1", $rows[1][1]);
    }

    public function test_participant_csv_can_be_reimported_with_quotes_backslashes_and_line_breaks(): void
    {
        $project = $this->project();
        $address = "Street \\\";=2+2\nȘcoală, România";
        $project->participants()->create(['complete_name' => 'Synthetic participant', 'address' => $address, 'phone' => '+4012345', 'role' => 'participant']);
        $csv = $this->get(route('projects.export-participants', $project))->assertOk()->streamedContent();
        $path = tempnam(sys_get_temp_dir(), 'csv-roundtrip-');
        try {
            file_put_contents($path, $csv);
            $this->assertSame(1, app(ParticipantCsvImporter::class)->import($project, $path));
            $imported = $project->participants()->latest('id')->first();
            $this->assertSame($address, $imported->address);
            $this->assertSame('+4012345', $imported->phone);
        } finally {
            unlink($path);
        }
    }

    public function test_platform_activity_export_protects_actor_email_and_description(): void
    {
        $this->project();
        PlatformAuditLog::create(['actor_id' => auth()->id(), 'action' => 'qa', 'description' => "\t=1+1"]);
        $response = PlatformActivityResource::exportCsv();
        ob_start();
        try {
            $response->sendContent();
            $csv = ob_get_contents();
        } finally {
            ob_end_clean();
        }
        $rows = $this->rows($csv);
        $this->assertSame("'+owner@example.test", $rows[1][1]);
        $this->assertSame("'\t=1+1", $rows[1][3]);
    }

    public function test_final_archive_csv_is_safe_and_structured_source_data_is_unchanged(): void
    {
        Storage::fake('local');
        $project = $this->project();
        $participant = $project->participants()->create(['complete_name' => '＠SUM(1,1)', 'phone' => '+40123456789', 'role' => 'participant']);
        $project->activityLogs()->create(['user_id' => auth()->id(), 'event' => 'qa', 'description' => "\r=1+1", 'subject_type' => Project::class, 'subject_id' => $project->id]);
        $selection = array_fill_keys(ProjectFinalArchive::CATEGORY_KEYS, false);
        $selection['participants'] = $selection['project_data'] = true;
        $path = app(ProjectFinalArchiveService::class)->create($project, $selection);
        try {
            $zip = new ZipArchive;
            $this->assertTrue($zip->open($path) === true);
            $rows = $this->rows($zip->getFromName('csv-safety/03-participants/participants.csv'), ';');
            $this->assertSame("'＠SUM(1,1)", $rows[1][0]);
            $this->assertSame("'+40123456789", $rows[1][5]);
            $activity = $this->rows($zip->getFromName('csv-safety/00-project-data/activity-log.csv'), ';');
            $this->assertSame("'\r=1+1", end($activity)[5]);
            $payload = json_decode($zip->getFromName('csv-safety/00-project-data/project-data.json'), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame('＠SUM(1,1)', $payload['participants'][0]['complete_name']);
            $zip->close();
            $this->assertSame('＠SUM(1,1)', $participant->fresh()->complete_name);
        } finally {
            unlink($path);
        }
    }
}
