<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Workspace;
use App\Services\ParticipantCsvImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ParticipantCsvImporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_the_export_format_and_maps_role_labels(): void
    {
        $project = $this->project();
        $path = $this->csv(implode("\n", [
            "\xEF\xBB\xBF\"Last name\",\"First name\",Organisation,Role,Country,\"Birth date\",Age,Nationality,Gender,Email,Phone,\"Fewer opportunities\",\"GDPR consent date\",\"Documents complete\"",
            'Pop,Ana,Association A,"Group leader",Romania,2000-04-12,26,Romanian,female,ana@example.test,+40123,Yes,2026-06-20,No',
        ]));

        $count = app(ParticipantCsvImporter::class)->import($project, $path);

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('participants', [
            'project_id' => $project->id,
            'first_name' => 'Ana',
            'last_name' => 'Pop',
            'role' => 'group_leader',
            'fewer_opportunities' => true,
        ]);
    }

    public function test_invalid_row_cancels_the_entire_import(): void
    {
        $project = $this->project();
        $path = $this->csv(implode("\n", [
            'Last name,First name,Email',
            'Adams,Ana,ana@example.test',
            'Zimmer,Zoe,not-an-email',
        ]));

        try {
            app(ParticipantCsvImporter::class)->import($project, $path);
            $this->fail('Expected validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('Row 3', $exception->errors()['importFile'][0]);
        }

        $this->assertDatabaseCount('participants', 0);
    }

    private function project(): Project
    {
        $workspace = Workspace::create(['name' => 'Import Workspace']);

        return Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Youth Exchange',
            'status' => 'active',
        ]);
    }

    private function csv(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'participants-');
        file_put_contents($path, $contents);

        return $path;
    }
}
