<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table): void {
            if (! Schema::hasColumn('participants', 'complete_name')) {
                $table->string('complete_name')->nullable()->after('project_id')->index();
            }
        });

        DB::table('participants')
            ->whereNull('complete_name')
            ->orderBy('id')
            ->chunkById(200, function ($participants): void {
                foreach ($participants as $participant) {
                    DB::table('participants')
                        ->where('id', $participant->id)
                        ->update([
                            'complete_name' => trim(((string) $participant->first_name).' '.((string) $participant->last_name)) ?: null,
                        ]);
                }
            });

        Schema::table('projects', function (Blueprint $table): void {
            if (! Schema::hasColumn('projects', 'participant_registration_token')) {
                $table->string('participant_registration_token', 80)->nullable()->unique()->after('action_data');
            }

            if (! Schema::hasColumn('projects', 'participant_registration_opened_at')) {
                $table->timestamp('participant_registration_opened_at')->nullable()->after('participant_registration_token');
            }

            if (! Schema::hasColumn('projects', 'participant_registration_closed_at')) {
                $table->timestamp('participant_registration_closed_at')->nullable()->after('participant_registration_opened_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            if (Schema::hasColumn('projects', 'participant_registration_closed_at')) {
                $table->dropColumn('participant_registration_closed_at');
            }

            if (Schema::hasColumn('projects', 'participant_registration_opened_at')) {
                $table->dropColumn('participant_registration_opened_at');
            }

            if (Schema::hasColumn('projects', 'participant_registration_token')) {
                $table->dropUnique(['participant_registration_token']);
                $table->dropColumn('participant_registration_token');
            }
        });

        Schema::table('participants', function (Blueprint $table): void {
            if (Schema::hasColumn('participants', 'complete_name')) {
                $table->dropColumn('complete_name');
            }
        });
    }
};
