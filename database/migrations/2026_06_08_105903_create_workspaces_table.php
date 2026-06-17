<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('plan')->default('free'); // free | writer | writer_pro

            // Date de facturare (completate la upgrade)
            $table->string('billing_name')->nullable();
            $table->string('billing_vat')->nullable();      // CUI / VAT
            $table->string('billing_address')->nullable();
            $table->string('billing_country', 2)->nullable(); // RO, DE, etc.

            // Valute workspace: {"RON": 5.07, "USD": 0.92} - rate fata de EUR
            $table->json('currencies')->nullable();

            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspaces');
    }
};
