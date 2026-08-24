<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SCOPED_DOCUMENT_CATEGORIES = [
        'mobility_plan',
        'mobility_material',
        'mobility_output',
        'mobility_photo_video',
        'mobility_other',
        'dissemination_evidence',
    ];

    public function up(): void
    {
        Schema::table('project_mobilities', function (Blueprint $table): void {
            $table->json('workspace_data')->nullable()->after('host_organisation');
        });

        Schema::table('project_documents', function (Blueprint $table): void {
            $table->foreignId('project_mobility_id')
                ->nullable()
                ->after('project_id')
                ->constrained('project_mobilities')
                ->nullOnDelete();
            $table->index(['project_mobility_id', 'category']);
        });

        DB::table('projects')->orderBy('id')->chunkById(100, function ($projects): void {
            foreach ($projects as $project) {
                $actionData = json_decode((string) ($project->action_data ?? '{}'), true) ?: [];
                $hasLegacyWorkspace = filled(data_get($actionData, 'mobility.report'))
                    || filled(data_get($actionData, 'mobility.photo_folder_url'))
                    || filled(data_get($actionData, 'mobility.photo_folder_links'))
                    || filled(data_get($actionData, 'mobility.final_video_url'))
                    || filled(data_get($actionData, 'mobility.evidence_days'))
                    || filled(data_get($actionData, 'dissemination_reports'));
                $hasLegacyDocuments = DB::table('project_documents')
                    ->where('project_id', $project->id)
                    ->whereIn('category', self::SCOPED_DOCUMENT_CATEGORIES)
                    ->exists();

                if (! $hasLegacyWorkspace && ! $hasLegacyDocuments) {
                    continue;
                }

                $mobility = DB::table('project_mobilities')
                    ->where('project_id', $project->id)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->first();

                if (! $mobility) {
                    $mobilityId = DB::table('project_mobilities')->insertGetId([
                        'project_id' => $project->id,
                        'name' => 'Mobility 1',
                        'start_date' => $project->mobility_start_date,
                        'end_date' => $project->mobility_end_date,
                        'sort_order' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $mobility = (object) ['id' => $mobilityId];
                }

                $workspace = [
                    'report' => data_get($actionData, 'mobility.report', ''),
                    'photo_folder_url' => data_get($actionData, 'mobility.photo_folder_url', ''),
                    'photo_folder_links' => data_get($actionData, 'mobility.photo_folder_links', []),
                    'final_video_url' => data_get($actionData, 'mobility.final_video_url', ''),
                    'evidence_days' => data_get($actionData, 'mobility.evidence_days', []),
                    'dissemination_reports' => data_get($actionData, 'dissemination_reports', []),
                ];

                DB::table('project_mobilities')->where('id', $mobility->id)->update([
                    'workspace_data' => json_encode($workspace),
                    'updated_at' => now(),
                ]);

                DB::table('project_documents')
                    ->where('project_id', $project->id)
                    ->whereIn('category', self::SCOPED_DOCUMENT_CATEGORIES)
                    ->whereNull('project_mobility_id')
                    ->update(['project_mobility_id' => $mobility->id]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_documents', function (Blueprint $table): void {
            $table->dropIndex(['project_mobility_id', 'category']);
            $table->dropConstrainedForeignId('project_mobility_id');
        });

        Schema::table('project_mobilities', function (Blueprint $table): void {
            $table->dropColumn('workspace_data');
        });
    }
};
