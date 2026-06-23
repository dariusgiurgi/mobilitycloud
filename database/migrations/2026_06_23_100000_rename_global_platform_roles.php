<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('role', User::ROLE_ADMIN)
            ->update(['role' => User::ROLE_PLATFORM_OWNER]);

        DB::table('users')
            ->where('role', User::ROLE_SUPERVISOR)
            ->update(['role' => User::ROLE_PLATFORM_ADMIN]);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('role', User::ROLE_PLATFORM_OWNER)
            ->update(['role' => User::ROLE_ADMIN]);

        DB::table('users')
            ->where('role', User::ROLE_PLATFORM_ADMIN)
            ->update(['role' => User::ROLE_SUPERVISOR]);
    }
};
