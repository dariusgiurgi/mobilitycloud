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

        $projectIds = DB::table('project_application_sections')
            ->whereNotNull('category')
            ->whereNotNull('question_key')
            ->where('question_key', 'not like', 'custom-%')
            ->distinct()
            ->pluck('project_id');

        foreach ($projectIds as $projectId) {
            if (DB::table('project_application_sections')->where('project_id', $projectId)->where('question_key', 'like', 'template-heading-%')->exists()) {
                continue;
            }

            $templateKey = ApplicationTemplates::normaliseKey((string) DB::table('projects')->where('id', $projectId)->value('ka_action'));
            $rows = DB::table('project_application_sections')->where('project_id', $projectId)->orderBy('sort_order')->orderBy('id')->get();
            $currentCategory = null;
            $categoryOccurrences = [];
            $nextSortOrder = 0;

            foreach ($rows as $row) {
                $questionKey = (string) ($row->question_key ?? '');
                $isOfficialQuestion = $questionKey !== ''
                    && ! str_starts_with($questionKey, 'custom-')
                    && ! str_starts_with($questionKey, 'template-heading-');
                $category = $isOfficialQuestion ? trim((string) ($row->category ?? '')) : '';

                if ($category !== '' && $category !== $currentCategory) {
                    $categoryOccurrences[$category] = ($categoryOccurrences[$category] ?? 0) + 1;
                    DB::table('project_application_sections')->insert([
                        'project_id' => $projectId,
                        'question_key' => 'template-heading-'.substr(sha1($templateKey.'|'.$category.'|'.$categoryOccurrences[$category]), 0, 16),
                        'title' => $category,
                        'content' => '',
                        'review_status' => 'draft',
                        'category' => null,
                        'char_limit' => null,
                        'sort_order' => $nextSortOrder++,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $currentCategory = $category;
                }

                DB::table('project_application_sections')->where('id', $row->id)->update(['sort_order' => $nextSortOrder++]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('project_application_sections')) {
            return;
        }

        $projectIds = DB::table('project_application_sections')->where('question_key', 'like', 'template-heading-%')->distinct()->pluck('project_id');
        DB::table('project_application_sections')->where('question_key', 'like', 'template-heading-%')->delete();

        foreach ($projectIds as $projectId) {
            DB::table('project_application_sections')
                ->where('project_id', $projectId)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->values()
                ->each(fn ($row, int $index) => DB::table('project_application_sections')->where('id', $row->id)->update(['sort_order' => $index]));
        }
    }
};
