<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_budget_line_id')->constrained('budget_lines')->cascadeOnDelete();
            $table->foreignId('to_budget_line_id')->constrained('budget_lines')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('reason', 500)->nullable();
            $table->string('status', 20)->default('active'); // active | reversed
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_transfers');
    }
};
