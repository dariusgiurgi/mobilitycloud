<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('attendance');
            $table->string('title');
            $table->string('activity_title')->nullable();
            $table->date('activity_date')->nullable();
            $table->string('location')->nullable();
            $table->json('metadata')->nullable();
            $table->string('signed_path')->nullable();
            $table->string('signed_disk')->default('local');
            $table->string('signed_name')->nullable();
            $table->unsignedBigInteger('signed_size')->default(0);
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'type']);
            $table->index(['project_id', 'activity_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_documents');
    }
};
