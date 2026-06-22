<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('content_blocks', 'imported_from_public_id')) {
            Schema::table('content_blocks', function (Blueprint $table) {
                $table->unsignedBigInteger('imported_from_public_id')->nullable()->after('source_note');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('content_blocks', 'imported_from_public_id')) {
            Schema::table('content_blocks', function (Blueprint $table) {
                $table->dropColumn('imported_from_public_id');
            });
        }
    }
};
