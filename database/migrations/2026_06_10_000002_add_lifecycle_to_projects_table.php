<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Lifecycle state. Existing rows default to 'writing'.
            if (! Schema::hasColumn('projects', 'status')) {
                $table->string('status')->default('writing')->after('id');
            }

            // Gate for the management/board side (activation fee, §6).
            if (! Schema::hasColumn('projects', 'is_activated')) {
                $table->boolean('is_activated')->default(false);
            }

            // Action-specific details kept flexible (KA152 flows, KA220 work
            // packages, ...) without a rigid schema. See architecture §7.
            if (! Schema::hasColumn('projects', 'action_data')) {
                $table->json('action_data')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // status and is_activated were introduced by the base projects
            // migration. Dropping them here would destroy pre-existing data.
            if (Schema::hasColumn('projects', 'action_data')) {
                $table->dropColumn('action_data');
            }
        });
    }
};
