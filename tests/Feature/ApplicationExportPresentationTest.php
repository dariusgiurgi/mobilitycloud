<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectApplicationSection;
use App\Models\User;
use App\Services\ApplicationWordExport;
use App\Support\ApplicationExportLayout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class ApplicationExportPresentationTest extends TestCase
{
    use RefreshDatabase;

    private function project(): Project
    {
        $project = Project::create(['owner_id' => User::factory()->create()->id, 'name' => 'Synthetic presentation project']);
        $project->applicationSections()->create(['question_key' => 'template-heading-summary', 'title' => 'Project summary', 'sort_order' => 0]);
        $project->applicationSections()->create(['question_key' => 'objectives', 'title' => 'What are the objectives?', 'category' => 'Project summary', 'content' => 'A complete answer with <literal> & characters.', 'sort_order' => 1]);
        $project->applicationSections()->create([
            'question_key' => 'custom-heading-notes', 'title' => 'Additional notes', 'content' => 'Preserve content even on a heading.', 'sort_order' => 2,
            'table_definitions' => [['key' => 'notes', 'label' => 'Supporting notes', 'columns' => [['field' => 'note', 'label' => 'Note']]]],
            'application_tables' => ['notes' => [['note' => 'Preserve this table cell.']]],
        ]);
        $project->applicationSections()->create(['question_key' => 'empty-question', 'title' => 'What remains unanswered?', 'sort_order' => 3]);

        return $project->load(['applicationSections' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')]);
    }

    public function test_pdf_view_renders_heading_once_without_a_false_missing_answer(): void
    {
        $project = $this->project();
        $html = view('pdf.application-report', ['project' => $project, 'sections' => $project->applicationSections])->render();

        $this->assertSame(1, substr_count($html, '>Project summary<'));
        $this->assertSame(1, substr_count($html, '— not answered —'));
        $this->assertStringContainsString('A complete answer with &lt;literal&gt; &amp; characters.', $html);
        $this->assertStringContainsString('Preserve content even on a heading.', $html);
        $this->assertStringContainsString('Preserve this table cell.', $html);
        $this->assertDatabaseCount('project_application_sections', 4);
    }

    public function test_word_renders_the_same_content_and_keeps_question_titles_with_answers(): void
    {
        $project = $this->project();
        $path = app(ApplicationWordExport::class)->create($project);
        try {
            $zip = new ZipArchive;
            $this->assertTrue($zip->open($path) === true);
            $xml = $zip->getFromName('word/document.xml');
            $styles = $zip->getFromName('word/styles.xml');
            $zip->close();
            $this->assertSame(1, substr_count($xml, '>Project summary<'));
            $this->assertSame(1, substr_count($xml, '— not answered —'));
            $this->assertStringContainsString('Preserve content even on a heading.', $xml);
            $this->assertStringContainsString('Preserve this table cell.', $xml);
            $this->assertStringContainsString('&lt;literal&gt; &amp; characters.', $xml);
            $this->assertStringContainsString('<w:keepNext', $styles);
        } finally {
            unlink($path);
        }
    }

    public function test_application_pack_uses_the_same_heading_and_answer_rules(): void
    {
        $project = $this->project();
        $html = view('pdf.application-pack', ['project' => $project, 'sections' => $project->applicationSections, 'flows' => [], 'budgetLines' => []])->render();

        $this->assertSame(1, substr_count($html, '>Project summary<'));
        $this->assertSame(1, substr_count($html, '— not answered —'));
        $this->assertStringContainsString('Preserve content even on a heading.', $html);
        $this->assertStringContainsString('Preserve this table cell.', $html);
        $this->assertStringContainsString('A complete answer with &lt;literal&gt; &amp; characters.', $html);
    }

    public function test_ordinary_questions_are_never_classified_as_headings_from_their_title(): void
    {
        $question = new ProjectApplicationSection(['question_key' => 'official-question', 'title' => 'Project summary', 'category' => 'Project summary']);
        $entry = ApplicationExportLayout::sections([$question])[0];
        $this->assertFalse($entry['is_heading']);
        $this->assertSame($question, $entry['section']);
    }
}
