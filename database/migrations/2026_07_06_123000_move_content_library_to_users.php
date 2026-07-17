<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_blocks', function (Blueprint $table): void {
            if (! Schema::hasColumn('content_blocks', 'owner_id')) {
                $table->foreignId('owner_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('content_blocks', function (Blueprint $table): void {
            if (Schema::hasColumn('content_blocks', 'owner_id')) {
                $table->dropConstrainedForeignId('owner_id');
            }
        });
    }
};
