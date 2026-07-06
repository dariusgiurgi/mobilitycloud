<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_blocks', function (Blueprint $table): void {
            if (! Schema::hasColumn('content_blocks', 'owner_id')) {
                $table->foreignId('owner_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            }
        });

        DB::table('content_blocks')
            ->whereNull('owner_id')
            ->select(['id', 'workspace_id'])
            ->orderBy('id')
            ->get()
            ->each(function ($block): void {
                $ownerId = DB::table('workspace_user')
                    ->where('workspace_id', $block->workspace_id)
                    ->where('role', 'owner')
                    ->orderBy('user_id')
                    ->value('user_id');

                if ($ownerId) {
                    DB::table('content_blocks')->where('id', $block->id)->update(['owner_id' => $ownerId]);
                }
            });

        DB::table('content_blocks')
            ->whereNull('owner_id')
            ->update(['owner_id' => DB::table('users')->min('id')]);

        Schema::table('content_blocks', function (Blueprint $table): void {
            if (Schema::hasColumn('content_blocks', 'workspace_id')) {
                try {
                    $table->dropForeign(['workspace_id']);
                } catch (Throwable) {
                    //
                }

                $table->unsignedBigInteger('workspace_id')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('content_blocks', function (Blueprint $table): void {
            if (Schema::hasColumn('content_blocks', 'owner_id')) {
                $table->dropConstrainedForeignId('owner_id');
            }
        });
    }
};
