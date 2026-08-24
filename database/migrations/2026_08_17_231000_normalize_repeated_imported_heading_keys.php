<?php

use App\Support\ApplicationTemplates;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('project_application_sections')) {
            return;
        }

        $projectIds = DB::table('project_application_sections')->where('question_key', 'like', 'template-heading-%')->distinct()->pluck('project_id');

        foreach ($projectIds as $projectId) {
            $templateKey = ApplicationTemplates::normaliseKey((string) DB::table('projects')->where('id', $projectId)->value('ka_action'));
            $rows = DB::table('project_application_sections')->where('project_id', $projectId)->orderBy('sort_order')->orderBy('id')->get()->values();
            $occurrences = [];

            foreach ($rows as $index => $row) {
                if (! str_starts_with((string) ($row->question_key ?? ''), 'template-heading-')) {
                    continue;
                }

                $nextQuestion = $rows->slice($index + 1)->first(function ($candidate) {
                    $key = (string) ($candidate->question_key ?? '');

                    return $key !== '' && ! str_contains($key, '-heading-') && ! str_starts_with($key, 'custom-');
                });
                $category = trim((string) ($nextQuestion->category ?? $row->title));
                $occurrences[$category] = ($occurrences[$category] ?? 0) + 1;

                DB::table('project_application_sections')->where('id', $row->id)->update([
                    'question_key' => 'template-heading-'.substr(sha1($templateKey.'|'.$category.'|'.$occurrences[$category]), 0, 16),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Heading records remain valid; the previous identifiers are intentionally not restored.
    }
};
