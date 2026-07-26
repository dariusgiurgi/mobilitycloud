<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_presences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('module', 40)->nullable();
            $table->timestamp('last_seen_at')->useCurrent();
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);
            $table->index(['project_id', 'module', 'last_seen_at']);
        });

        Schema::create('project_module_locks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('module', 40);
            $table->string('lock_key', 120)->default('__module__');
            $table->string('lock_label')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique(['project_id', 'module', 'lock_key']);
            $table->index(['project_id', 'module', 'lock_key', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_module_locks');
        Schema::dropIfExists('project_presences');
    }
};
