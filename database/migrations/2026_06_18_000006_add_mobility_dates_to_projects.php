<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (! Schema::hasColumn('projects', 'mobility_start_date')) {
                $table->date('mobility_start_date')->nullable()->after('ka_action');
            }
            if (! Schema::hasColumn('projects', 'mobility_end_date')) {
                $table->date('mobility_end_date')->nullable()->after('mobility_start_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            foreach (['mobility_start_date', 'mobility_end_date'] as $col) {
                if (Schema::hasColumn('projects', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};