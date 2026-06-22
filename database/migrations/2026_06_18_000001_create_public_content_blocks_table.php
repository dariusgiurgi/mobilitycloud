<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('public_content_blocks')) {
            return;
        }

        Schema::create('public_content_blocks', function (Blueprint $table) {
            $table->id();

            // Autorul (cine a publicat) — doar el poate edita/sterge.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Workspace-ul din care a fost publicat (pentru referinta).
            $table->foreignId('origin_workspace_id')->nullable()->constrained('workspaces')->nullOnDelete();

            $table->string('title');
            $table->string('category')->default('other');
            $table->string('ka_action')->default('any');
            $table->string('language', 2)->default('en');

            $table->longText('body');
            $table->json('tags')->nullable();

            // Daca e marcat "verificat", sursa devine obligatorie (validat in formular).
            $table->boolean('is_proven')->default(false);
            $table->string('source_note')->nullable();

            // De cate ori a fost importat de alti useri in biblioteca lor personala.
            $table->unsignedInteger('import_count')->default(0);

            $table->timestamps();

            $table->index(['category']);
            $table->index(['ka_action']);
            $table->index(['language']);
            $table->index(['user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_content_blocks');
    }
};
