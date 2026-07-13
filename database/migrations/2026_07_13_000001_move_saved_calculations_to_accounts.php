<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saved_calculations', function (Blueprint $table): void {
            if (! Schema::hasColumn('saved_calculations', 'workspace_id')) {
                return;
            }

            try {
                $table->dropForeign(['workspace_id']);
            } catch (Throwable) {
                //
            }

            $table->unsignedBigInteger('workspace_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // The product no longer requires saved calculations to belong to a
        // workspace, so the down migration intentionally keeps the safer nullable
        // account-level shape.
    }
};
