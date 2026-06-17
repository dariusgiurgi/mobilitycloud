<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('projects', 'ka_action')) {
            Schema::table('projects', function (Blueprint $table) {
                // The Erasmus+ action the application targets: ka122 / ka152 /
                // ka210 / ka220. Set when a template is imported in Application.
                $table->string('ka_action')->nullable()->after('grant_ref');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('projects', 'ka_action')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn('ka_action');
            });
        }
    }
};
