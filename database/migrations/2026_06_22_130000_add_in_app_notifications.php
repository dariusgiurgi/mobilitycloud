<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::table('project_tasks', function (Blueprint $table): void {
            $table->timestamp('reminder_sent_at')->nullable()->after('completed_at');
            $table->timestamp('overdue_notified_at')->nullable()->after('reminder_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('project_tasks', function (Blueprint $table): void {
            $table->dropColumn(['reminder_sent_at', 'overdue_notified_at']);
        });

        Schema::dropIfExists('notifications');
    }
};
