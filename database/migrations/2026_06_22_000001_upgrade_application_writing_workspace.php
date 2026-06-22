<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_application_sections', function (Blueprint $table) {
            $table->string('question_key')->nullable()->after('project_id');
            $table->string('review_status')->default('draft')->after('content');
            $table->text('internal_notes')->nullable()->after('review_status');
            $table->index(['project_id', 'question_key']);
        });

        Schema::create('project_application_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('label');
            $table->string('template_key')->nullable();
            $table->json('snapshot');
            $table->timestamps();
            $table->index(['project_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_application_versions');
        Schema::table('project_application_sections', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'question_key']);
            $table->dropColumn(['question_key', 'review_status', 'internal_notes']);
        });
    }
};
