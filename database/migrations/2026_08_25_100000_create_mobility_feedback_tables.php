<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_forms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('intro_text')->nullable();
            $table->text('thank_you_text')->nullable();
            $table->json('questions');
            $table->boolean('is_archived')->default(false);
            $table->timestamps();

            $table->index(['owner_id', 'is_archived']);
        });

        Schema::create('mobility_feedback_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_mobility_id')->constrained('project_mobilities')->cascadeOnDelete();
            $table->foreignId('feedback_form_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('public_token', 96)->unique();
            $table->json('form_snapshot');
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['project_mobility_id', 'opened_at']);
        });

        Schema::create('mobility_feedback_responses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mobility_feedback_campaign_id')->constrained()->cascadeOnDelete();
            $table->json('answers');
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->index(['mobility_feedback_campaign_id', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobility_feedback_responses');
        Schema::dropIfExists('mobility_feedback_campaigns');
        Schema::dropIfExists('feedback_forms');
    }
};
