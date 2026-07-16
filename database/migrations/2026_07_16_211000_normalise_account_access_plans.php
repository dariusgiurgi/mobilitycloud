<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'plan')) {
            return;
        }

        DB::table('users')
            ->whereIn('plan', ['free', 'writer', 'writer_pro'])
            ->update(['plan' => 'standard']);

        DB::table('users')
            ->where('plan', 'demo')
            ->update([
                'plan' => 'unlimited',
                'subscription_status' => 'active',
            ]);

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY plan VARCHAR(255) NOT NULL DEFAULT 'standard'");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE users ALTER COLUMN plan SET DEFAULT 'standard'");
        } elseif ($driver === 'sqlite') {
            // SQLite cannot safely alter this default without rebuilding the table.
            // Runtime defaults and account creation flows still set standard access.
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'plan')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY plan VARCHAR(255) NOT NULL DEFAULT 'free'");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE users ALTER COLUMN plan SET DEFAULT 'free'");
        }
    }
};
