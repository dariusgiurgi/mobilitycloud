<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_mobilities', function (Blueprint $table): void {
            $table->json('participating_organisations')->nullable()->after('host_organisation');
        });
    }

    public function down(): void
    {
        Schema::table('project_mobilities', function (Blueprint $table): void {
            $table->dropColumn('participating_organisations');
        });
    }
};
