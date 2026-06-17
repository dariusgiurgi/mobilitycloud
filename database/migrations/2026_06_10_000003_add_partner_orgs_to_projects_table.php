<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('projects', 'partner_orgs')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->json('partner_orgs')->nullable()->after('partner_org');
            });
        }

        // Backfill: carry the existing single partner into the new list so no
        // data is lost. Each entry: name / country / oid.
        if (Schema::hasColumn('projects', 'partner_org')) {
            DB::table('projects')
                ->whereNotNull('partner_org')
                ->where('partner_org', '!=', '')
                ->whereNull('partner_orgs')
                ->orderBy('id')
                ->get(['id', 'partner_org'])
                ->each(function ($row) {
                    DB::table('projects')->where('id', $row->id)->update([
                        'partner_orgs' => json_encode([
                            ['name' => $row->partner_org, 'country' => null, 'oid' => null],
                        ]),
                    ]);
                });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('projects', 'partner_orgs')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn('partner_orgs');
            });
        }
    }
};
