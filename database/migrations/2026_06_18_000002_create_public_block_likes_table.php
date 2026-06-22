<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('public_block_likes')) {
            Schema::create('public_block_likes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('public_content_block_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                // Un singur like per user per bloc.
                $table->unique(['user_id', 'public_content_block_id'], 'pbl_user_block_unique');
            });
        }

        if (! Schema::hasColumn('public_content_blocks', 'likes_count')) {
            Schema::table('public_content_blocks', function (Blueprint $table) {
                $table->unsignedInteger('likes_count')->default(0)->after('import_count');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('public_block_likes');

        if (Schema::hasColumn('public_content_blocks', 'likes_count')) {
            Schema::table('public_content_blocks', function (Blueprint $table) {
                $table->dropColumn('likes_count');
            });
        }
    }
};
