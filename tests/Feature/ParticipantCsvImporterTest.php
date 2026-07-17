<?php

namespace Tests\Feature;

use App\Models\Project;
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
            "\xEF\xBB\xBF\"Last name\";\"First name\";Organisation;Role;Country;\"Birth date\";Age;Nationality;Gender;Email;Phone;Address;\"Medical conditions\";Allergies;\"Dietary restrictions\";\"Special needs\";\"Fewer opportunities\";\"Guardian name\";\"Guardian contact\";\"GDPR consent date\";\"Documents complete\"",
            "Pop;Ana;Association A;\"Group leader\";Romania;2000-04-12;26;Romanian;female;ana@example.test;'+40123;\"Main Street 10\";Asthma;Pollen;Vegetarian;\"Wheelchair access\";Yes;\"Maria Pop\";'+40999;2026-06-20;No",
        ]));

        $count = app(ParticipantCsvImporter::class)->import($project, $path);

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('participants', [
            'project_id' => $project->id,
            'first_name' => 'Ana',
            'last_name' => 'Pop',
            'role' => 'group_leader',
            'fewer_opportunities' => true,
            'phone' => '+40123',
            'address' => 'Main Street 10',
            'medical_conditions' => 'Asthma',
            'allergies' => 'Pollen',
            'dietary_restrictions' => 'Vegetarian',
            'special_needs' => 'Wheelchair access',
            'guardian_name' => 'Maria Pop',
            'guardian_contact' => '+40999',
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

    public function test_it_normalises_dates_reformatted_by_excel(): void
    {
        $project = $this->project();
        $path = $this->csv(implode("\n", [
            'Last name;First name;Birth date;GDPR consent date',
            'Pop;Ana;12.04.2000;20/06/2026',
            'Marin;Daria;45292;',
        ]));

        $count = app(ParticipantCsvImporter::class)->import($project, $path);

        $this->assertSame(2, $count);
        $this->assertDatabaseHas('participants', [
            'last_name' => 'Pop',
            'birth_date' => '2000-04-12 00:00:00',
            'gdpr_consented_at' => '2026-06-20 00:00:00',
        ]);
        $this->assertDatabaseHas('participants', [
            'last_name' => 'Marin',
            'birth_date' => '2024-01-01 00:00:00',
        ]);
    }

    private function project(): Project
    {
        return Project::create([
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
