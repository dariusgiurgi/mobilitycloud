<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->timestamp('invoice_refunded_at')->nullable();
            $table->decimal('invoice_refund_amount', 12, 2)->nullable();
            $table->string('invoice_refund_reference')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['invoice_refunded_at', 'invoice_refund_amount', 'invoice_refund_reference']);
        });
    }
};
