<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('type')->default('individual_support'); // tipul de calcul
            $table->json('inputs');   // toate input-urile
            $table->json('results');  // rezultatele (is/travel/os/total)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_calculations');
    }
};
