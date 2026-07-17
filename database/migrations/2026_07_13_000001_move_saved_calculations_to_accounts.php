<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        //
    }

    public function down(): void
    {
        // The product no longer requires saved calculations to belong to a
        // workspace, so the down migration intentionally keeps the safer nullable
        // account-level shape.
    }
};
