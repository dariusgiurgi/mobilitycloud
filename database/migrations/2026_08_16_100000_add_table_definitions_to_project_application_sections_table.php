<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_application_sections', function (Blueprint $table) {
            if (! Schema::hasColumn('project_application_sections', 'table_definitions')) {
                $table->json('table_definitions')->nullable()->after('application_tables');
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_application_sections', function (Blueprint $table) {
            if (Schema::hasColumn('project_application_sections', 'table_definitions')) {
                $table->dropColumn('table_definitions');
            }
        });
    }
};
