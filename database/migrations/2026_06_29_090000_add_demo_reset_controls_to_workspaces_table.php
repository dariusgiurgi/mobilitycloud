<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table): void {
            if (! Schema::hasColumn('workspaces', 'demo_reset_frequency')) {
                $table->string('demo_reset_frequency')->default('manual')->after('is_internal');
            }

            if (! Schema::hasColumn('workspaces', 'demo_last_reset_at')) {
                $table->timestamp('demo_last_reset_at')->nullable()->after('demo_reset_frequency');
            }
        });
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table): void {
            if (Schema::hasColumn('workspaces', 'demo_last_reset_at')) {
                $table->dropColumn('demo_last_reset_at');
            }

            if (Schema::hasColumn('workspaces', 'demo_reset_frequency')) {
                $table->dropColumn('demo_reset_frequency');
            }
        });
    }
};
