<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_documents', function (Blueprint $table) {
            $table->string('category')->nullable()->after('type');
            $table->date('document_date')->nullable()->after('location');
            $table->text('notes')->nullable()->after('document_date');
            $table->string('file_path')->nullable()->after('metadata');
            $table->string('file_disk')->default('local')->after('file_path');
            $table->string('file_name')->nullable()->after('file_disk');
            $table->unsignedBigInteger('file_size')->default(0)->after('file_name');

            $table->index(['project_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::table('project_documents', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'category']);
            $table->dropColumn([
                'category', 'document_date', 'notes',
                'file_path', 'file_disk', 'file_name', 'file_size',
            ]);
        });
    }
};
