<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_line_id')->constrained()->cascadeOnDelete();

            $table->string('reference_nr')->nullable();
            $table->string('description')->nullable();
            $table->date('expense_date')->nullable();

            $table->decimal('amount', 15, 2)->nullable();
            $table->string('currency', 10)->default('EUR');
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->decimal('amount_eur', 15, 2)->nullable();

            $table->boolean('is_civil_convention')->default(false);
            $table->json('convention_data')->nullable();

            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->text('notes')->nullable();

            $table->integer('position')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
