<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->timestamp('invoice_cancelled_at')->nullable();
            $table->foreignId('invoice_cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('invoice_cancellation_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invoice_cancelled_by');
            $table->dropColumn(['invoice_cancelled_at', 'invoice_cancellation_reason']);
        });
    }
};
