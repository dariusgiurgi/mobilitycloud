<?php

use App\Models\Project;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            if (! Schema::hasColumn('projects', 'currencies')) {
                $table->json('currencies')->nullable()->after('withholding_tax_rate');
            }
        });

        DB::table('project_user')
            ->where('role', Project::PROJECT_ROLE_MANAGER)
            ->update(['role' => Project::PROJECT_ROLE_EDITOR]);

        DB::table('workspace_invitations')
            ->where('role', 'project_manager')
            ->update(['role' => 'project_editor']);

        DB::table('projects')
            ->join('workspaces', 'workspaces.id', '=', 'projects.workspace_id')
            ->whereNull('projects.currencies')
            ->whereNotNull('workspaces.currencies')
            ->select('projects.id', 'workspaces.currencies')
            ->orderBy('projects.id')
            ->get()
            ->each(function ($project): void {
                $rows = [];
                $currencies = json_decode($project->currencies, true);

                if (! is_array($currencies)) {
                    return;
                }

                foreach ($currencies as $code => $rate) {
                    $code = strtoupper(trim((string) $code));
                    $rate = is_array($rate) ? ($rate['rate'] ?? null) : $rate;

                    if ($code === '' || $code === 'EUR' || strlen($code) !== 3 || ! is_numeric($rate) || (float) $rate <= 0) {
                        continue;
                    }

                    $rows[] = ['code' => $code, 'rate' => (float) $rate];
                }

                DB::table('projects')
                    ->where('id', $project->id)
                    ->update(['currencies' => $rows === [] ? null : json_encode($rows)]);
            });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            if (Schema::hasColumn('projects', 'currencies')) {
                $table->dropColumn('currencies');
            }
        });
    }
};
