<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('project_application_sections') || ! Schema::hasColumn('project_application_sections', 'title')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE project_application_sections MODIFY title TEXT NOT NULL');

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE project_application_sections ALTER COLUMN title TYPE TEXT');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('project_application_sections') || ! Schema::hasColumn('project_application_sections', 'title')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE project_application_sections MODIFY title VARCHAR(255) NOT NULL');

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE project_application_sections ALTER COLUMN title TYPE VARCHAR(255)');
        }
    }
};
