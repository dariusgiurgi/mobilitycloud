<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_final_archives', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('status', 20)->default('queued');
            $table->json('selection');
            $table->char('selection_hash', 64);
            $table->string('filename');
            $table->string('disk')->nullable();
            $table->string('path')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->char('sha256', 64)->nullable();
            $table->string('failure_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('downloaded_at')->nullable();
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'selection_hash']);
            $table->index(['expires_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_final_archives');
    }
};
