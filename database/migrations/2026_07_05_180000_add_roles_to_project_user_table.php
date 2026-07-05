<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_user', function (Blueprint $table): void {
            if (! Schema::hasColumn('project_user', 'role')) {
                $table->string('role')->default('editor')->after('user_id');
            }
        });

        DB::table('project_user')
            ->whereNull('role')
            ->orWhere('role', '')
            ->update(['role' => 'editor']);
    }

    public function down(): void
    {
        Schema::table('project_user', function (Blueprint $table): void {
            if (Schema::hasColumn('project_user', 'role')) {
                $table->dropColumn('role');
            }
        });
    }
};
