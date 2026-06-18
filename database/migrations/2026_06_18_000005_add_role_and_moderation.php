<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('user')->after('email');
            });
        }

        // Bloc ascuns de un moderator (ramane in DB, dar nu apare in biblioteca).
        if (! Schema::hasColumn('public_content_blocks', 'is_hidden')) {
            Schema::table('public_content_blocks', function (Blueprint $table) {
                $table->boolean('is_hidden')->default(false)->after('likes_count');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', fn (Blueprint $t) => $t->dropColumn('role'));
        }
        if (Schema::hasColumn('public_content_blocks', 'is_hidden')) {
            Schema::table('public_content_blocks', fn (Blueprint $t) => $t->dropColumn('is_hidden'));
        }
    }
};