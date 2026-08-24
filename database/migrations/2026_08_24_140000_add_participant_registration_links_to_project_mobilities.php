<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_mobilities', function (Blueprint $table): void {
            $table->string('participant_registration_token', 80)->nullable()->unique()->after('workspace_data');
            $table->timestamp('participant_registration_opened_at')->nullable()->after('participant_registration_token');
            $table->timestamp('participant_registration_closed_at')->nullable()->after('participant_registration_opened_at');
        });
    }

    public function down(): void
    {
        Schema::table('project_mobilities', function (Blueprint $table): void {
            $table->dropUnique(['participant_registration_token']);
            $table->dropColumn([
                'participant_registration_token',
                'participant_registration_opened_at',
                'participant_registration_closed_at',
            ]);
        });
    }
};
