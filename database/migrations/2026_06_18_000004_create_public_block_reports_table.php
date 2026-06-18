<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('public_block_reports')) {
            return;
        }

        Schema::create('public_block_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('public_content_block_id')->constrained()->cascadeOnDelete();

            $table->string('reason');          // spam | inaccurate | copyright | offensive | other
            $table->text('details')->nullable();
            $table->string('status')->default('pending'); // pending | reviewed | dismissed

            $table->timestamps();

            // Un singur raport per user per bloc.
            $table->unique(['user_id', 'public_content_block_id'], 'pbr_user_block_unique');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_block_reports');
    }
};