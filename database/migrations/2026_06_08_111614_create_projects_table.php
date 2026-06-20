<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('acronym')->nullable();
            $table->string('grant_ref')->nullable();
            $table->text('description')->nullable();

            // Lifecycle values are defined by App\Enums\ProjectStatus.
            $table->string('status')->default('writing');

            // Buget
            $table->decimal('total_budget', 15, 2)->default(0);
            $table->decimal('approved_budget', 15, 2)->nullable();
            $table->decimal('first_tranche_pct', 5, 2)->default(80);
            $table->decimal('withholding_tax_rate', 5, 2)->default(10);

            // Activare
            $table->boolean('is_activated')->default(false);
            $table->timestamp('activated_at')->nullable();
            $table->string('activation_tier')->nullable();
            $table->json('activation_snapshot')->nullable();
            $table->string('activation_payment_id')->nullable();

            // Cod cheltuieli configurabil
            $table->string('expense_prefix')->default('EXP');
            $table->unsignedTinyInteger('expense_pad_length')->default(3);

            // Date
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('partner_org')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
