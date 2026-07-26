<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('projects')
            ->where('status', 'active')
            ->update(['status' => 'approved']);
    }

    public function down(): void
    {
        // The separate "active" project lifecycle stage was intentionally
        // removed from the product flow. Reversing this data normalisation
        // would require knowing which approved projects were formerly active.
    }
};
