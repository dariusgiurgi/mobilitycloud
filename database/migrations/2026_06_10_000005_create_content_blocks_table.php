<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('content_blocks')) {
            return;
        }

        Schema::create('content_blocks', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->string('category')->default('other');
            $table->string('ka_action')->default('any');
            $table->string('language', 2)->default('en');

            $table->longText('body');
            $table->json('tags')->nullable();

            $table->boolean('is_proven')->default(false);
            $table->string('source_note')->nullable();
            $table->unsignedInteger('usage_count')->default(0);

            $table->timestamps();

            $table->index(['category']);
            $table->index(['ka_action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_blocks');
    }
};
