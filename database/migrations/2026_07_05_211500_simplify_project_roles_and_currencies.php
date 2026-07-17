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

        DB::table('project_invitations')
            ->where('role', 'project_manager')
            ->update(['role' => 'project_editor']);

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
