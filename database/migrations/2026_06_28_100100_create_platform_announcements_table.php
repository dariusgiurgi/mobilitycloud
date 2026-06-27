<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_announcements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('message');
            $table->string('severity')->default('info');
            $table->string('audience')->default('all');
            $table->json('plans')->nullable();
            $table->json('workspace_ids')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_dismissible')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
            $table->index('audience');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_announcements');
    }
};
