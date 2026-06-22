<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('participant_attachments')) {
            return;
        }

        Schema::create('participant_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();

            $table->string('type');           // gdpr | parental | agreement | enrollment | id_copy | insurance | other
            $table->string('path');           // calea pe disk (public)
            $table->string('original_name')->nullable();
            $table->unsignedBigInteger('size')->default(0);

            $table->timestamps();

            // Un singur document per tip per participant.
            $table->unique(['participant_id', 'type'], 'part_attach_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participant_attachments');
    }
};
