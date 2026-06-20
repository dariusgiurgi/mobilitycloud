<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participant_attachments', function (Blueprint $table) {
            $table->string('disk')->default('local')->after('path');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->string('attachment_disk')->default('local')->after('attachment_path');
        });

        $this->moveFiles('participant_attachments', 'path', 'disk');
        $this->moveFiles('expenses', 'attachment_path', 'attachment_disk');
    }

    public function down(): void
    {
        Schema::table('participant_attachments', fn (Blueprint $table) => $table->dropColumn('disk'));
        Schema::table('expenses', fn (Blueprint $table) => $table->dropColumn('attachment_disk'));
    }

    private function moveFiles(string $table, string $pathColumn, string $diskColumn): void
    {
        DB::table($table)
            ->whereNotNull($pathColumn)
            ->orderBy('id')
            ->each(function (object $row) use ($table, $pathColumn, $diskColumn): void {
                $path = $row->{$pathColumn};

                if (Storage::disk('public')->exists($path)) {
                    $copied = Storage::disk('local')->put($path, Storage::disk('public')->get($path));

                    if ($copied) {
                        Storage::disk('public')->delete($path);
                    }
                }

                DB::table($table)->where('id', $row->id)->update([$diskColumn => 'local']);
            });
    }
};
