<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_application_sections', function (Blueprint $table) {
            if (! Schema::hasColumn('project_application_sections', 'application_tables')) {
                $table->json('application_tables')->nullable()->after('content');
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_application_sections', function (Blueprint $table) {
            if (Schema::hasColumn('project_application_sections', 'application_tables')) {
                $table->dropColumn('application_tables');
            }
        });
    }
};
