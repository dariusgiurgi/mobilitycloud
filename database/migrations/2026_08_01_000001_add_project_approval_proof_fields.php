<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            if (! Schema::hasColumn('projects', 'approved_grant_proof_path')) {
                $table->string('approved_grant_proof_path')->nullable()->after('approved_declared_by');
            }

            if (! Schema::hasColumn('projects', 'approved_grant_proof_disk')) {
                $table->string('approved_grant_proof_disk', 32)->nullable()->after('approved_grant_proof_path');
            }

            if (! Schema::hasColumn('projects', 'approved_grant_proof_original_name')) {
                $table->string('approved_grant_proof_original_name')->nullable()->after('approved_grant_proof_disk');
            }

            if (! Schema::hasColumn('projects', 'approved_grant_proof_uploaded_at')) {
                $table->timestamp('approved_grant_proof_uploaded_at')->nullable()->after('approved_grant_proof_original_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            foreach ([
                'approved_grant_proof_uploaded_at',
                'approved_grant_proof_original_name',
                'approved_grant_proof_disk',
                'approved_grant_proof_path',
            ] as $column) {
                if (Schema::hasColumn('projects', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
