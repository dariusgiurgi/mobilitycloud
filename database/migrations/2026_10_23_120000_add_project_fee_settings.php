<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_fee_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->decimal('percentage', 5, 2);
        });
        DB::table('project_fee_settings')->insert(['id' => 1, 'percentage' => 0.50]);
        Schema::table('users', function (Blueprint $table) {
            $table->string('project_fee_mode', 20)->default('global');
            $table->decimal('project_fee_value', 12, 2)->nullable();
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->json('project_fee_snapshot')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('projects', fn (Blueprint $table) => $table->dropColumn('project_fee_snapshot'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['project_fee_mode', 'project_fee_value']));
        Schema::dropIfExists('project_fee_settings');
    }
};
