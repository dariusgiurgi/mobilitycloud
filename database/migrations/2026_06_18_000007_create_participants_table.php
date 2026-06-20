<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('participants')) {
            return;
        }

        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();

            // Identitate
            $table->string('first_name');
            $table->string('last_name');
            $table->date('birth_date')->nullable();
            $table->string('nationality')->nullable();
            $table->string('gender')->nullable();

            // Apartenenta
            $table->string('partner_organisation')->nullable();
            $table->string('country')->nullable();
            $table->string('role')->default('participant'); // participant|group_leader|facilitator|accompanying_person|trainer

            // Contact
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();

            // Date sensibile (vor fi criptate la etapa 3)
            $table->text('medical_conditions')->nullable();
            $table->text('allergies')->nullable();
            $table->text('dietary_restrictions')->nullable();
            $table->text('special_needs')->nullable();
            $table->boolean('fewer_opportunities')->default(false);

            // Reprezentant legal (pentru minori)
            $table->string('guardian_name')->nullable();
            $table->string('guardian_contact')->nullable();

            // GDPR
            $table->timestamp('gdpr_consented_at')->nullable();

            $table->timestamps();

            $table->index('project_id');
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};